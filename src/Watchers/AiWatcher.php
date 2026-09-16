<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentFailedOver;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StartingStep;
use Laravel\Ai\Events\StepCompleted;
use Laravel\Ai\Events\StepFailed;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolFailed;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\ToolNameResolver;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\ExceptionContext;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Throwable;

class AiWatcher extends Watcher
{
    /**
     * The listener methods keyed by the Laravel AI event they handle.
     *
     * @var array<class-string, string>
     */
    protected static $events = [
        PromptingAgent::class => 'recordPrompting',
        StreamingAgent::class => 'recordPrompting',
        AgentPrompted::class => 'recordPrompted',
        AgentStreamed::class => 'recordPrompted',
        AgentFailed::class => 'recordFailed',
        AgentFailedOver::class => 'recordFailedOver',
        StartingStep::class => 'recordStartingStep',
        StepCompleted::class => 'recordStepCompleted',
        StepFailed::class => 'recordStepFailed',
        InvokingTool::class => 'recordInvokingTool',
        ToolInvoked::class => 'recordToolInvoked',
        ToolFailed::class => 'recordToolFailed',
    ];

    /**
     * The entry UUID and lifecycle summaries of each in-flight run, keyed by invocation ID.
     *
     * @var array<string, array<string, mixed>>
     */
    protected $runs = [];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        foreach (static::$events as $event => $listener) {
            if (class_exists($event)) {
                $app['events']->listen($event, [$this, $listener]);
            }
        }

        Telescope::afterStoring(function () {
            $this->runs = [];

            return true;
        });
    }

    /**
     * Record the start of an agent run.
     *
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    public function recordPrompting(PromptingAgent $event)
    {
        // A failover re-prompts the same invocation against the next provider...
        if (! Telescope::isRecording() || isset($this->runs[$event->invocationId])) {
            return;
        }

        $prompt = $event->prompt;

        $entry = IncomingEntry::make([
            'status' => 'running',
            'agent' => get_class($prompt->agent),
            'provider' => $prompt->provider->name(),
            'model' => $prompt->model,
            'streaming' => $event instanceof StreamingAgent,
            'invocation_id' => $event->invocationId,
            'parent_invocation_id' => $prompt->parentInvocationId,
            'timeout' => $prompt->timeout,
            'attachments' => $prompt->attachments->count(),
            'prompt' => $this->content($prompt->prompt),
            'steps' => [],
            'tools' => [],
            'failovers' => [],
            'pending_approvals' => [],
        ])->withFamilyHash($prompt->parentInvocationId ?? $event->invocationId);

        $entry->tags([get_class($prompt->agent)]);

        $this->runs[$event->invocationId] = [
            'uuid' => $entry->uuid,
            'steps' => [],
            'tools' => [],
            'failovers' => [],
        ];

        Telescope::recordAi($entry);

        return $entry;
    }

    /**
     * Record a completed agent run.
     *
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordPrompted(AgentPrompted $event)
    {
        if (! $run = $this->run($event->invocationId)) {
            return;
        }

        $response = $event->response;

        return $this->update($run, array_merge($this->summaries($run), [
            'status' => $response->hasPendingApprovals() ? 'waiting_for_approval' : 'completed',
            'provider' => $response->meta->provider,
            'model' => $response->meta->model,
            'conversation_id' => $response->conversationId,
            'usage' => $response->usage->toArray(),
            'finish_reason' => $response->steps->last()?->finishReason->value,
            'response' => $this->content($response->text),
            'pending_approvals' => $response->pendingApprovals->map(fn ($approval) => [
                'id' => $approval->id,
                'tool' => $approval->tool,
                'reason' => $approval->reason,
                'arguments' => $this->content($approval->arguments),
            ])->all(),
            'exception' => null,
        ]))->removeTags(['failed']);
    }

    /**
     * Record a failed agent run.
     *
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordFailed(AgentFailed $event)
    {
        if (! $run = $this->run($event->invocationId)) {
            return;
        }

        // The run is kept in memory because a failover may still retry this invocation...
        return $this->update($run, array_merge($this->summaries($run), [
            'status' => 'failed',
            'exception' => $this->exception($event->exception),
        ]))->addTags(['failed']);
    }

    /**
     * Record an agent failing over to the next configured provider.
     *
     * @return void
     */
    public function recordFailedOver(AgentFailedOver $event)
    {
        if (! $this->run($event->invocationId)) {
            return;
        }

        $this->runs[$event->invocationId]['failovers'][] = [
            'provider' => $event->provider->name(),
            'model' => $event->model,
            'exception' => $this->exception($event->exception),
        ];
    }

    /**
     * Record a provider step that is starting.
     *
     * @return void
     */
    public function recordStartingStep(StartingStep $event)
    {
        $this->recordStep($event, ['status' => 'running']);
    }

    /**
     * Record a provider step that returned a response.
     *
     * @return void
     */
    public function recordStepCompleted(StepCompleted $event)
    {
        $this->recordStep($event, [
            'status' => 'completed',
            'duration' => $event->time,
            'finish_reason' => $event->response->finishReason->value,
            'usage' => $event->response->usage->toArray(),
            'tool_calls' => count($event->response->toolCalls),
            'text' => $this->content($event->response->text),
        ]);
    }

    /**
     * Record a provider step that threw.
     *
     * @return void
     */
    public function recordStepFailed(StepFailed $event)
    {
        $this->recordStep($event, [
            'status' => 'failed',
            'duration' => $event->time,
            'exception' => $this->exception($event->exception),
        ]);
    }

    /**
     * Record a tool that is being invoked.
     *
     * @return void
     */
    public function recordInvokingTool(InvokingTool $event)
    {
        $this->recordTool($event, ['status' => 'running']);
    }

    /**
     * Record a tool that returned a result.
     *
     * @return void
     */
    public function recordToolInvoked(ToolInvoked $event)
    {
        $this->recordTool($event, [
            'status' => 'completed',
            'duration' => $event->time,
            'result' => $this->content($event->result),
        ]);
    }

    /**
     * Record a tool whose handler threw.
     *
     * @return void
     */
    public function recordToolFailed(ToolFailed $event)
    {
        $this->recordTool($event, [
            'status' => 'failed',
            'duration' => $event->time,
            'exception' => $this->exception($event->exception),
        ]);
    }

    /**
     * Merge a summary into the step it belongs to.
     *
     * @param  \Laravel\Ai\Events\StartingStep|\Laravel\Ai\Events\StepCompleted|\Laravel\Ai\Events\StepFailed  $event
     * @param  array  $summary
     * @return void
     */
    protected function recordStep($event, array $summary)
    {
        if (! $this->run($event->invocationId)) {
            return;
        }

        $steps = &$this->runs[$event->invocationId]['steps'];

        $steps[$event->stepNumber] = array_merge($steps[$event->stepNumber] ?? [], [
            'step' => $event->stepNumber,
            'provider' => $event->provider->name(),
            'model' => $event->model,
            'final' => $event->isFinalStep,
        ], $summary);
    }

    /**
     * Merge a summary into the tool invocation it belongs to.
     *
     * @param  \Laravel\Ai\Events\InvokingTool|\Laravel\Ai\Events\ToolInvoked|\Laravel\Ai\Events\ToolFailed  $event
     * @param  array  $summary
     * @return void
     */
    protected function recordTool($event, array $summary)
    {
        if (! $this->run($event->invocationId)) {
            return;
        }

        $tools = &$this->runs[$event->invocationId]['tools'];

        $tools[$event->toolInvocationId] = array_merge($tools[$event->toolInvocationId] ?? [], [
            'id' => $event->toolInvocationId,
            'tool' => ToolNameResolver::resolve($event->tool),
            'tool_class' => get_class($event->tool),
            'arguments' => $this->content($event->arguments),
        ], $summary);
    }

    /**
     * Get the in-flight state for the given invocation.
     *
     * @param  string  $invocationId
     * @return array|null
     */
    protected function run($invocationId)
    {
        return Telescope::isRecording() ? ($this->runs[$invocationId] ?? null) : null;
    }

    /**
     * Get the lifecycle summaries collected for the given run.
     *
     * @param  array  $run
     * @return array
     */
    protected function summaries(array $run)
    {
        return [
            'steps' => array_values($run['steps']),
            'tools' => array_values($run['tools']),
            'failovers' => $run['failovers'],
            'duration' => array_sum(array_column($run['steps'], 'duration')) ?: null,
        ];
    }

    /**
     * Queue an update for the given run's entry.
     *
     * @param  array  $run
     * @param  array  $changes
     * @return \Laravel\Telescope\EntryUpdate
     */
    protected function update(array $run, array $changes)
    {
        Telescope::recordUpdate($update = EntryUpdate::make($run['uuid'], EntryType::AI, $changes));

        return $update;
    }

    /**
     * Summarize the given exception.
     *
     * @return array
     */
    protected function exception(Throwable $exception)
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'line' => $exception->getLine(),
            'line_preview' => ExceptionContext::get($exception),
        ];
    }

    /**
     * Prepare an opt-in payload for storage.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function content($value)
    {
        if (! ($this->options['content'] ?? false)) {
            return null;
        }

        $encoded = json_encode(
            $this->hideParameters($value, Telescope::$hiddenRequestParameters),
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        if ($encoded === false || ! $this->contentWithinLimits($encoded)) {
            return 'Purged By Telescope';
        }

        return json_decode($encoded, true);
    }
}
