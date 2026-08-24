<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Arr;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Throwable;

class AiWatcher extends Watcher
{
    /**
     * The Telescope entry UUIDs keyed by Laravel AI invocation ID.
     *
     * @var array<string, string>
     */
    protected $entryUuids = [];

    /**
     * The provider step summaries keyed by Laravel AI invocation ID.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected $steps = [];

    /**
     * The tool invocation summaries keyed by Laravel AI invocation ID.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected $tools = [];

    /**
     * The failover summaries keyed by Laravel AI invocation ID.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected $failovers = [];

    /**
     * The approval checkpoint summaries keyed by Laravel AI invocation ID.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected $approvals = [];

    /**
     * The total provider step counts keyed by Laravel AI invocation ID.
     *
     * @var array<string, int>
     */
    protected $stepCounts = [];

    /**
     * The total tool invocation counts keyed by Laravel AI invocation ID.
     *
     * @var array<string, int>
     */
    protected $toolCounts = [];

    /**
     * The total failover counts keyed by Laravel AI invocation ID.
     *
     * @var array<string, int>
     */
    protected $failoverCounts = [];

    /**
     * The total approval checkpoint counts keyed by Laravel AI invocation ID.
     *
     * @var array<string, int>
     */
    protected $approvalCounts = [];

    /**
     * The pending approval counts keyed by Laravel AI invocation ID.
     *
     * @var array<string, int>
     */
    protected $pendingApprovalCounts = [];

    /**
     * The Laravel AI events that should be monitored.
     *
     * @var array<string, string>
     */
    protected static $events = [
        'Laravel\\Ai\\Events\\PromptingAgent' => 'recordPromptingAgent',
        'Laravel\\Ai\\Events\\StreamingAgent' => 'recordStreamingAgent',
        'Laravel\\Ai\\Events\\AgentPrompted' => 'recordAgentPrompted',
        'Laravel\\Ai\\Events\\AgentStreamed' => 'recordAgentStreamed',
        'Laravel\\Ai\\Events\\AgentFailed' => 'recordAgentFailed',
        'Laravel\\Ai\\Events\\AgentFailedOver' => 'recordAgentFailedOver',
        'Laravel\\Ai\\Events\\StartingStep' => 'recordStartingStep',
        'Laravel\\Ai\\Events\\StepCompleted' => 'recordStepCompleted',
        'Laravel\\Ai\\Events\\StepFailed' => 'recordStepFailed',
        'Laravel\\Ai\\Events\\InvokingTool' => 'recordInvokingTool',
        'Laravel\\Ai\\Events\\ToolInvoked' => 'recordToolInvoked',
        'Laravel\\Ai\\Events\\ToolFailed' => 'recordToolFailed',
        'Laravel\\Ai\\Events\\ToolApprovalRequested' => 'recordToolApprovalRequested',
        'Laravel\\Ai\\Events\\ToolApprovalResolved' => 'recordToolApprovalResolved',
    ];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        foreach (static::$events as $event => $listener) {
            if (is_int($event)) {
                $event = $listener;
                $listener = 'recordAiEvent';
            }

            if (class_exists($event)) {
                $app['events']->listen($event, [$this, $listener]);
            }
        }
    }

    /**
     * Record a Laravel AI sync run start event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    public function recordPromptingAgent(object $event)
    {
        return $this->recordRunStart($event, false);
    }

    /**
     * Record a Laravel AI streaming run start event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    public function recordStreamingAgent(object $event)
    {
        return $this->recordRunStart($event, true);
    }

    /**
     * Record a Laravel AI sync run completion event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordAgentPrompted(object $event)
    {
        return $this->recordRunCompletion($event);
    }

    /**
     * Record a Laravel AI streaming run completion event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordAgentStreamed(object $event)
    {
        return $this->recordRunCompletion($event);
    }

    /**
     * Record a Laravel AI run failure event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordAgentFailed(object $event)
    {
        if (! Telescope::isRecording() || ! isset($event->exception) || ! $event->exception instanceof Throwable) {
            return null;
        }

        $uuid = $this->entryUuid($event->invocationId ?? $event->prompt->invocationId ?? null);

        if (! $uuid) {
            return null;
        }

        $update = EntryUpdate::make($uuid, EntryType::AI, [
            'status' => 'failed',
            'exception' => $this->summarizeException($event->exception),
        ])->addTags(['failed']);

        Telescope::recordUpdate($update);

        $this->flushInvocationState($event->invocationId ?? $event->prompt->invocationId ?? null);

        return $update;
    }

    /**
     * Record a Laravel AI provider step start event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordStartingStep(object $event)
    {
        return $this->recordStep($event, [
            'status' => 'running',
            'message_count' => $this->countable($event->messages ?? []),
        ]);
    }

    /**
     * Record a Laravel AI provider step completion event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordStepCompleted(object $event)
    {
        return $this->recordStep($event, array_merge(
            [
                'status' => 'completed',
                'duration' => $event->time ?? null,
            ],
            isset($event->response) && is_object($event->response)
                ? $this->summarizeStepResponse($event->response)
                : []
        ));
    }

    /**
     * Record a Laravel AI provider step failure event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordStepFailed(object $event)
    {
        return $this->recordStep($event, $this->filled([
            'status' => 'failed',
            'duration' => $event->time ?? null,
            'exception' => isset($event->exception) && $event->exception instanceof Throwable
                ? $this->summarizeException($event->exception)
                : null,
        ]));
    }

    /**
     * Record a Laravel AI tool invocation start event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordInvokingTool(object $event)
    {
        return $this->recordTool($event, [
            'status' => 'running',
            'arguments' => $this->safePayload($event->arguments ?? null, 'tool_arguments'),
        ]);
    }

    /**
     * Record a Laravel AI tool invocation completion event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordToolInvoked(object $event)
    {
        return $this->recordTool($event, [
            'status' => 'completed',
            'duration' => $event->time ?? null,
            'arguments' => $this->safePayload($event->arguments ?? null, 'tool_arguments'),
            'result' => $this->safePayload($event->result ?? null, 'tool_results'),
        ]);
    }

    /**
     * Record a Laravel AI tool invocation failure event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordToolFailed(object $event)
    {
        return $this->recordTool($event, [
            'status' => 'failed',
            'duration' => $event->time ?? null,
            'arguments' => $this->safePayload($event->arguments ?? null, 'tool_arguments'),
            'exception' => isset($event->exception) && $event->exception instanceof Throwable
                ? $this->summarizeException($event->exception)
                : null,
        ]);
    }

    /**
     * Record a Laravel AI provider failover event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordAgentFailedOver(object $event)
    {
        return $this->recordFailover($event, $this->filled([
            'status' => 'failed_over',
            'agent' => $this->className($event->agent ?? null),
            'provider' => $this->providerName($event->provider ?? null),
            'model' => $event->model ?? null,
            'exception' => isset($event->exception) && $event->exception instanceof Throwable
                ? $this->summarizeException($event->exception)
                : null,
        ]));
    }

    /**
     * Record a Laravel AI tool approval request event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordToolApprovalRequested(object $event)
    {
        return $this->recordApproval($event, $this->filled([
            'status' => 'requested',
            'agent' => $this->className($event->agent ?? null),
            'conversation_id' => $event->conversationId ?? null,
            'conversation_user' => $this->conversationUserIdentifier($event->conversationUser ?? null),
            'pending_approval_count' => $this->countable($event->pendingApprovals ?? []),
            'pending_approvals' => $this->summaries($event->pendingApprovals ?? [], function ($approval) {
                return $this->summarizePendingApproval(is_object($approval) ? $approval : (object) $approval);
            }),
        ]), $this->countable($event->pendingApprovals ?? []));
    }

    /**
     * Record a Laravel AI tool approval resolution event.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    public function recordToolApprovalResolved(object $event)
    {
        return $this->recordApproval($event, $this->filled([
            'status' => 'resolved',
            'agent' => $this->className($event->agent ?? null),
            'conversation_id' => $event->conversationId ?? null,
            'conversation_user' => $this->conversationUserIdentifier($event->conversationUser ?? null),
            'tool_result_count' => $this->countable($event->toolResults ?? []),
            'tool_results' => $this->summaries($event->toolResults ?? [], function ($toolResult) {
                return $this->summarizeToolResult(is_object($toolResult) ? $toolResult : (object) $toolResult);
            }),
        ]), 0);
    }

    /**
     * Record a Laravel AI event.
     *
     * @param  object  $event
     * @return void
     */
    public function recordAiEvent(object $event)
    {
        //
    }

    /**
     * Record a Laravel AI run start.
     *
     * @param  object  $event
     * @param  bool  $streaming
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    protected function recordRunStart(object $event, $streaming)
    {
        if (! Telescope::isRecording() || ! isset($event->prompt) || ! is_object($event->prompt)) {
            return null;
        }

        $invocationId = $event->invocationId ?? $event->prompt->invocationId ?? null;

        if (is_string($invocationId) && isset($this->entryUuids[$invocationId])) {
            return null;
        }

        $entry = IncomingEntry::make(array_merge($this->summarizePrompt($event->prompt), [
            'status' => 'running',
            'invocation_id' => $invocationId,
            'streaming' => $streaming,
            'steps' => [],
            'tools' => [],
            'failovers' => [],
            'approvals' => [],
            'step_count' => 0,
            'tool_count' => 0,
            'failover_count' => 0,
            'approval_count' => 0,
        ]), $this->validUuid($invocationId) ? $invocationId : null);

        if (is_string($invocationId) && $invocationId !== '') {
            $this->entryUuids[$invocationId] = $entry->uuid;
            $entry->withFamilyHash($invocationId);
        }

        Telescope::recordAi($entry);

        return $entry;
    }

    /**
     * Record a Laravel AI run completion.
     *
     * @param  object  $event
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    protected function recordRunCompletion(object $event)
    {
        if (! Telescope::isRecording() || ! isset($event->response) || ! is_object($event->response)) {
            return null;
        }

        $uuid = $this->entryUuid($event->invocationId ?? $event->response->invocationId ?? null);

        if (! $uuid) {
            return null;
        }

        $response = $this->summarizeResponse($event->response);
        $invocationId = $event->invocationId ?? $event->response->invocationId ?? null;
        $pendingApprovalCount = max($response['pending_approval_count'] ?? 0, $this->pendingApprovalCount($invocationId));
        $stepCount = max($response['step_count'] ?? 0, $this->stepCount($invocationId));
        $toolCount = max($response['tool_call_count'] ?? 0, $this->toolCount($invocationId));
        $failoverCount = $this->failoverCount($invocationId);
        $approvalCount = $this->approvalCount($invocationId);
        $status = $pendingApprovalCount > 0 ? 'waiting_for_approval' : 'completed';

        $changes = $this->filled([
            'status' => $status,
            'finish_reason' => $this->finishReason($event->response),
            'usage' => $response['usage'] ?? null,
            'response' => $response,
            'step_count' => $stepCount,
            'tool_count' => $toolCount,
            'failover_count' => $failoverCount,
            'approval_count' => $approvalCount,
            'pending_approval_count' => $pendingApprovalCount,
        ]);

        $changes['exception'] = null;

        $update = EntryUpdate::make($uuid, EntryType::AI, $changes)->removeTags(['failed']);

        Telescope::recordUpdate($update);

        if ($status === 'completed') {
            $this->flushInvocationState($invocationId);
        }

        return $update;
    }

    /**
     * Record a Laravel AI provider failover summary.
     *
     * @param  object  $event
     * @param  array  $summary
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    protected function recordFailover(object $event, array $summary)
    {
        if (! Telescope::isRecording()) {
            return null;
        }

        $invocationId = $event->invocationId ?? null;
        $uuid = $this->entryUuid($invocationId);

        if (! $uuid) {
            return null;
        }

        $this->failovers[$invocationId][] = $summary;
        $this->failoverCounts[$invocationId] = ($this->failoverCounts[$invocationId] ?? 0) + 1;
        $this->limitCachedSummaries($this->failovers[$invocationId], 'max_failovers');

        $failovers = $this->limitedSummaries($this->failovers[$invocationId], 'max_failovers');

        $update = EntryUpdate::make($uuid, EntryType::AI, [
            'failovers' => $failovers,
            'failover_count' => $this->failoverCounts[$invocationId],
        ]);

        Telescope::recordUpdate($update);

        return $update;
    }

    /**
     * Record a Laravel AI tool approval checkpoint summary.
     *
     * @param  object  $event
     * @param  array  $summary
     * @param  int  $pendingApprovalCount
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    protected function recordApproval(object $event, array $summary, $pendingApprovalCount)
    {
        if (! Telescope::isRecording()) {
            return null;
        }

        $invocationId = $event->invocationId ?? null;
        $uuid = $this->entryUuid($invocationId);

        if (! $uuid) {
            return null;
        }

        $this->pendingApprovalCounts[$invocationId] = $pendingApprovalCount;
        $this->approvals[$invocationId][] = $summary;
        $this->approvalCounts[$invocationId] = ($this->approvalCounts[$invocationId] ?? 0) + 1;
        $this->limitCachedSummaries($this->approvals[$invocationId], 'max_approvals');

        $approvals = $this->limitedSummaries($this->approvals[$invocationId], 'max_approvals');

        $update = EntryUpdate::make($uuid, EntryType::AI, [
            'approvals' => $approvals,
            'approval_count' => $this->approvalCounts[$invocationId],
            'pending_approval_count' => $pendingApprovalCount,
        ]);

        Telescope::recordUpdate($update);

        return $update;
    }

    /**
     * Record or update a Laravel AI tool invocation summary.
     *
     * @param  object  $event
     * @param  array  $summary
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    protected function recordTool(object $event, array $summary)
    {
        if (! Telescope::isRecording()) {
            return null;
        }

        $invocationId = $event->invocationId ?? null;
        $uuid = $this->entryUuid($invocationId);

        if (! $uuid || ! isset($event->toolInvocationId)) {
            return null;
        }

        if (! isset($this->tools[$invocationId][$event->toolInvocationId])) {
            $this->toolCounts[$invocationId] = ($this->toolCounts[$invocationId] ?? 0) + 1;
        }

        $this->tools[$invocationId][$event->toolInvocationId] = array_merge(
            $this->tools[$invocationId][$event->toolInvocationId] ?? [],
            $this->toolBaseSummary($event),
            $this->filled($summary)
        );
        $this->limitCachedSummaries($this->tools[$invocationId], 'max_tools');

        $tools = $this->limitedSummaries(array_values($this->tools[$invocationId]), 'max_tools');

        $update = EntryUpdate::make($uuid, EntryType::AI, [
            'tools' => $tools,
            'tool_count' => $this->toolCounts[$invocationId] ?? count($tools),
        ]);

        Telescope::recordUpdate($update);

        return $update;
    }

    /**
     * Record or update a Laravel AI provider step summary.
     *
     * @param  object  $event
     * @param  array  $summary
     * @return \Laravel\Telescope\EntryUpdate|null
     */
    protected function recordStep(object $event, array $summary)
    {
        if (! Telescope::isRecording()) {
            return null;
        }

        $invocationId = $event->invocationId ?? null;
        $uuid = $this->entryUuid($invocationId);

        if (! $uuid || ! isset($event->stepNumber)) {
            return null;
        }

        if (is_numeric($event->stepNumber)) {
            $this->stepCounts[$invocationId] = max(
                $this->stepCounts[$invocationId] ?? 0,
                (int) $event->stepNumber
            );
        } elseif (! isset($this->steps[$invocationId][$event->stepNumber])) {
            $this->stepCounts[$invocationId] = ($this->stepCounts[$invocationId] ?? 0) + 1;
        }

        $this->steps[$invocationId][$event->stepNumber] = array_merge(
            $this->steps[$invocationId][$event->stepNumber] ?? [],
            $this->stepBaseSummary($event),
            $this->filled($summary)
        );

        ksort($this->steps[$invocationId]);
        $this->limitCachedSummaries($this->steps[$invocationId], 'max_steps');

        $steps = $this->limitedSummaries(array_values($this->steps[$invocationId]), 'max_steps');

        $update = EntryUpdate::make($uuid, EntryType::AI, [
            'steps' => $steps,
            'step_count' => $this->stepCounts[$invocationId] ?? count($steps),
        ]);

        Telescope::recordUpdate($update);

        return $update;
    }

    /**
     * Summarize a Laravel AI prompt without storing sensitive content by default.
     *
     * @param  object  $prompt
     * @return array
     */
    protected function summarizePrompt(object $prompt)
    {
        return $this->filled([
            'invocation_id' => $prompt->invocationId ?? null,
            'parent_invocation_id' => $prompt->parentInvocationId ?? null,
            'parent_tool_invocation_id' => $prompt->parentToolInvocationId ?? null,
            'agent' => $this->className($prompt->agent ?? null),
            'provider' => $this->providerName($prompt->provider ?? null),
            'model' => $prompt->model ?? null,
            'timeout' => $prompt->timeout ?? null,
            'attachment_count' => $this->countable($prompt->attachments ?? []),
            'prompt' => $this->safePayload($prompt->prompt ?? null, 'content'),
        ]);
    }

    /**
     * Summarize a Laravel AI response without storing sensitive content by default.
     *
     * @param  object  $response
     * @return array
     */
    protected function summarizeResponse(object $response)
    {
        $meta = $this->toArray($response->meta ?? null);

        return $this->filled([
            'invocation_id' => $response->invocationId ?? null,
            'usage' => $this->toArray($response->usage ?? null),
            'meta' => $this->filled([
                'provider' => $meta['provider'] ?? null,
                'model' => $meta['model'] ?? null,
            ]),
            'step_count' => $this->countable($response->steps ?? []),
            'tool_call_count' => $this->countable($response->toolCalls ?? []),
            'tool_result_count' => $this->countable($response->toolResults ?? []),
            'message_count' => $this->countable($response->messages ?? []),
            'pending_approval_count' => $this->countable($response->pendingApprovals ?? []),
            'text' => $this->safePayload($response->text ?? null, 'content'),
            'messages' => $this->safePayload($response->messages ?? null, 'messages'),
            'raw' => $this->safeRawPayload($response->raw ?? null),
        ]);
    }

    /**
     * Summarize a Laravel AI step response without storing sensitive content by default.
     *
     * @param  object  $response
     * @return array
     */
    protected function summarizeStepResponse(object $response)
    {
        $meta = $this->toArray($response->meta ?? null);

        return $this->filled([
            'finish_reason' => $this->enumValue($response->finishReason ?? null),
            'usage' => $this->toArray($response->usage ?? null),
            'meta' => $this->filled([
                'provider' => $meta['provider'] ?? null,
                'model' => $meta['model'] ?? null,
            ]),
            'tool_call_count' => $this->countable($response->toolCalls ?? []),
            'pending_approval_count' => $this->countable($response->pendingApprovals ?? []),
            'has_structured' => ($response->structured ?? null) !== null,
            'text' => $this->safePayload($response->text ?? null, 'content'),
            'structured' => $this->safePayload($response->structured ?? null, 'content'),
            'raw' => $this->safeRawPayload($response->raw ?? null),
        ]);
    }

    /**
     * Build common provider step summary fields.
     *
     * @param  object  $event
     * @return array
     */
    protected function stepBaseSummary(object $event)
    {
        return $this->filled([
            'step_number' => $event->stepNumber ?? null,
            'agent' => $this->className($event->agent ?? null),
            'provider' => $this->providerName($event->provider ?? null),
            'model' => $event->model ?? null,
            'final' => $event->isFinalStep ?? null,
        ]);
    }

    /**
     * Build common tool invocation summary fields.
     *
     * @param  object  $event
     * @return array
     */
    protected function toolBaseSummary(object $event)
    {
        return $this->filled([
            'id' => $event->toolInvocationId ?? null,
            'agent' => $this->className($event->agent ?? null),
            'tool' => $this->toolName($event->tool ?? null),
            'tool_class' => $this->className($event->tool ?? null),
        ]);
    }

    /**
     * Summarize a Laravel AI tool call without storing arguments by default.
     *
     * @param  object  $toolCall
     * @return array
     */
    protected function summarizeToolCall(object $toolCall)
    {
        return $this->filled([
            'id' => $toolCall->id ?? null,
            'name' => $toolCall->name ?? null,
            'result_id' => $toolCall->resultId ?? null,
            'reasoning_id' => $toolCall->reasoningId ?? null,
            'arguments' => $this->safePayload($toolCall->arguments ?? null, 'tool_arguments'),
        ]);
    }

    /**
     * Summarize a Laravel AI tool result without storing arguments or results by default.
     *
     * @param  object  $toolResult
     * @return array
     */
    protected function summarizeToolResult(object $toolResult)
    {
        return $this->filled([
            'id' => $toolResult->id ?? null,
            'name' => $toolResult->name ?? null,
            'result_id' => $toolResult->resultId ?? null,
            'denied' => $toolResult->denied ?? null,
            'arguments' => $this->safePayload($toolResult->arguments ?? null, 'tool_arguments'),
            'result' => $this->safePayload($toolResult->result ?? null, 'tool_results'),
        ]);
    }

    /**
     * Summarize a pending tool approval.
     *
     * @param  object  $approval
     * @return array
     */
    protected function summarizePendingApproval(object $approval)
    {
        return $this->filled([
            'id' => $approval->id ?? $approval->toolCallId ?? null,
            'name' => $approval->name ?? $approval->toolName ?? $approval->tool ?? null,
            'arguments' => $this->safePayload($approval->arguments ?? null, 'tool_arguments'),
            'reason' => $this->safePayload($approval->reason ?? null, 'content'),
        ]);
    }

    /**
     * Summarize an exception without serializing arbitrary objects.
     *
     * @param  \Throwable  $exception
     * @return array
     */
    protected function summarizeException(Throwable $exception)
    {
        return $this->filled([
            'class' => get_class($exception),
            'message' => $this->safePayload($exception->getMessage(), 'content'),
            'code' => $exception->getCode(),
        ]);
    }

    /**
     * Safely include an opt-in payload.
     *
     * @param  mixed  $value
     * @param  string  $option
     * @return mixed
     */
    protected function safePayload($value, $option)
    {
        if (! ($this->options[$option] ?? false)) {
            return null;
        }

        $payload = $this->redactAiPayload($this->toArray($value));

        return $this->contentWithinAiLimits($payload) ? $payload : 'Purged By Telescope';
    }

    /**
     * Safely include an opt-in raw provider response.
     *
     * @param  mixed  $response
     * @return mixed
     */
    protected function safeRawPayload($response)
    {
        if (! ($this->options['raw'] ?? false)) {
            return null;
        }

        return $this->safePayload($this->rawResponsePayload($response), 'raw');
    }

    /**
     * Extract useful data from response-like objects without depending on their classes.
     *
     * @param  mixed  $response
     * @return mixed
     */
    protected function rawResponsePayload($response)
    {
        if (! is_object($response)) {
            return $response;
        }

        if (method_exists($response, 'json')) {
            try {
                $json = $response->json();

                if ($json !== null) {
                    return $json;
                }
            } catch (Throwable $e) {
                //
            }
        }

        if (method_exists($response, 'body')) {
            try {
                return $response->body();
            } catch (Throwable $e) {
                //
            }
        }

        return ['class' => get_class($response)];
    }

    /**
     * Redact sensitive AI payload fields.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function redactAiPayload($value)
    {
        if (! is_array($value)) {
            return $this->jsonSafeScalar($value);
        }

        foreach ($this->options['hidden'] ?? [] as $parameter) {
            if (Arr::get($value, $parameter) !== null) {
                Arr::set($value, $parameter, '********');
            }
        }

        foreach ($value as $key => $item) {
            if ($this->isHiddenAiKey($key)) {
                $value[$key] = '********';

                continue;
            }

            $value[$key] = $this->redactAiPayload($item);
        }

        return $value;
    }

    /**
     * Determine if an AI payload key matches a hidden parameter variant.
     *
     * @param  mixed  $key
     * @return bool
     */
    protected function isHiddenAiKey($key)
    {
        if (! is_string($key)) {
            return false;
        }

        $normalizedKey = $this->normalizeAiHiddenKey($key);

        foreach ($this->options['hidden'] ?? [] as $hidden) {
            $segments = explode('.', $hidden);
            $lastSegment = end($segments);

            if ($normalizedKey === $this->normalizeAiHiddenKey($lastSegment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize hidden AI payload keys for case-insensitive variant matching.
     *
     * @param  string  $key
     * @return string
     */
    protected function normalizeAiHiddenKey($key)
    {
        return strtolower(str_replace(['-', '_'], '', $key));
    }

    /**
     * Limit stored lifecycle summaries while keeping total counts separately.
     *
     * @param  array  $summaries
     * @param  string  $option
     * @return array
     */
    protected function limitedSummaries(array $summaries, $option)
    {
        $limit = $this->options[$option] ?? 50;

        if (! is_numeric($limit) || $limit < 1) {
            return [];
        }

        return array_slice($summaries, -((int) $limit));
    }

    /**
     * Limit cached lifecycle summaries while preserving their correlation keys.
     *
     * @param  array  $summaries
     * @param  string  $option
     * @return void
     */
    protected function limitCachedSummaries(array &$summaries, $option)
    {
        $limit = $this->options[$option] ?? 50;

        if (! is_numeric($limit) || $limit < 1) {
            $summaries = [];

            return;
        }

        $summaries = array_slice($summaries, -((int) $limit), null, true);
    }

    /**
     * Determine if a value fits within the AI watcher size limit.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function contentWithinAiLimits($value)
    {
        $encoded = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);

        if ($encoded === false) {
            return false;
        }

        return mb_strlen($encoded) / 1000 <= ($this->options['size_limit'] ?? 64);
    }

    /**
     * Get an object's class name.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function className($value)
    {
        return is_object($value) ? get_class($value) : null;
    }

    /**
     * Get an enum-like value without requiring PHP enum support.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function enumValue($value)
    {
        if (! is_object($value)) {
            return $value;
        }

        return $value->value ?? $value->name ?? $this->className($value);
    }

    /**
     * Convert safe values and Arrayable-like objects to arrays.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function toArray($value)
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->toArray($item), $value);
        }

        return is_object($value)
            ? ['class' => get_class($value)]
            : $value;
    }

    /**
     * Summarize each item in an array or collection-like value.
     *
     * @param  mixed  $items
     * @param  callable  $callback
     * @return array
     */
    protected function summaries($items, callable $callback)
    {
        if (is_object($items) && method_exists($items, 'all')) {
            $items = $items->all();
        }

        if ($items instanceof \Traversable) {
            $items = iterator_to_array($items);
        }

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map($callback, $items));
    }

    /**
     * Remove null values from a summary.
     *
     * @param  array  $summary
     * @return array
     */
    protected function filled(array $summary)
    {
        return array_filter($summary, fn ($value) => $value !== null);
    }

    /**
     * Count arrays, collections, and countable values.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function countable($value)
    {
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }

        return 0;
    }

    /**
     * Resolve a provider name when available.
     *
     * @param  mixed  $provider
     * @return string|null
     */
    protected function providerName($provider)
    {
        if (is_object($provider) && method_exists($provider, 'name')) {
            return $provider->name();
        }

        return is_string($provider) ? $provider : $this->className($provider);
    }

    /**
     * Resolve a tool name when possible.
     *
     * @param  mixed  $tool
     * @return string|null
     */
    protected function toolName($tool)
    {
        if (! is_object($tool)) {
            return is_string($tool) ? $tool : null;
        }

        if (class_exists($resolver = 'Laravel\\Ai\\Tools\\ToolNameResolver')) {
            return $resolver::resolve($tool);
        }

        if (method_exists($tool, 'name')) {
            return $tool->name();
        }

        return class_basename($tool);
    }

    /**
     * Get a safe conversation user identifier when one is exposed.
     *
     * @param  mixed  $user
     * @return array|null
     */
    protected function conversationUserIdentifier($user)
    {
        if (! is_object($user)) {
            return null;
        }

        $id = null;

        if (method_exists($user, 'getAuthIdentifier')) {
            $id = $user->getAuthIdentifier();
        } elseif (method_exists($user, 'getKey')) {
            $id = $user->getKey();
        } elseif (isset($user->id)) {
            $id = $user->id;
        }

        return $this->filled([
            'class' => get_class($user),
            'id' => is_scalar($id) ? $this->jsonSafeScalar($id) : null,
        ]);
    }

    /**
     * Resolve a Telescope entry UUID from a Laravel AI invocation ID.
     *
     * @param  mixed  $invocationId
     * @return string|null
     */
    protected function entryUuid($invocationId)
    {
        if (! is_string($invocationId) || $invocationId === '') {
            return null;
        }

        return $this->entryUuids[$invocationId] ?? ($this->validUuid($invocationId) ? $invocationId : null);
    }

    /**
     * Count cached provider steps for an invocation.
     *
     * @param  mixed  $invocationId
     * @return int
     */
    protected function stepCount($invocationId)
    {
        return is_string($invocationId) && isset($this->steps[$invocationId])
            ? count($this->steps[$invocationId])
            : 0;
    }

    /**
     * Count cached tool invocations for an invocation.
     *
     * @param  mixed  $invocationId
     * @return int
     */
    protected function toolCount($invocationId)
    {
        return is_string($invocationId) && isset($this->tools[$invocationId])
            ? count($this->tools[$invocationId])
            : 0;
    }

    /**
     * Count cached provider failovers for an invocation.
     *
     * @param  mixed  $invocationId
     * @return int
     */
    protected function failoverCount($invocationId)
    {
        return is_string($invocationId) && isset($this->failovers[$invocationId])
            ? count($this->failovers[$invocationId])
            : 0;
    }

    /**
     * Count cached approval checkpoints for an invocation.
     *
     * @param  mixed  $invocationId
     * @return int
     */
    protected function approvalCount($invocationId)
    {
        return is_string($invocationId) && isset($this->approvals[$invocationId])
            ? count($this->approvals[$invocationId])
            : 0;
    }

    /**
     * Count cached pending tool approvals for an invocation.
     *
     * @param  mixed  $invocationId
     * @return int
     */
    protected function pendingApprovalCount($invocationId)
    {
        return is_string($invocationId) && isset($this->pendingApprovalCounts[$invocationId])
            ? $this->pendingApprovalCounts[$invocationId]
            : 0;
    }

    /**
     * Flush cached lifecycle state for a completed invocation.
     *
     * @param  mixed  $invocationId
     * @return void
     */
    protected function flushInvocationState($invocationId)
    {
        if (! is_string($invocationId) || $invocationId === '') {
            return;
        }

        unset(
            $this->entryUuids[$invocationId],
            $this->steps[$invocationId],
            $this->tools[$invocationId],
            $this->failovers[$invocationId],
            $this->approvals[$invocationId],
            $this->stepCounts[$invocationId],
            $this->toolCounts[$invocationId],
            $this->failoverCounts[$invocationId],
            $this->approvalCounts[$invocationId],
            $this->pendingApprovalCounts[$invocationId]
        );
    }

    /**
     * Resolve a response finish reason when one is exposed.
     *
     * @param  object  $response
     * @return mixed
     */
    protected function finishReason(object $response)
    {
        if (isset($response->finishReason)) {
            return $this->enumValue($response->finishReason);
        }

        $step = $this->lastValue($response->steps ?? null);

        if (is_object($step) && isset($step->finishReason)) {
            return $this->enumValue($step->finishReason);
        }

        if (is_array($step)) {
            return $step['finish_reason'] ?? null;
        }

        $event = $this->lastValue($response->events ?? null);

        if (is_object($event) && isset($event->reason)) {
            return $event->reason;
        }

        if (is_array($event)) {
            return $event['reason'] ?? null;
        }
    }

    /**
     * Get the last value from an array or collection-like object.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function lastValue($value)
    {
        if (is_object($value) && method_exists($value, 'last')) {
            return $value->last();
        }

        if (is_array($value) && $value !== []) {
            return end($value);
        }
    }

    /**
     * Normalize scalar payload values for JSON storage.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function jsonSafeScalar($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);

        return $encoded === false ? null : json_decode($encoded, true);
    }

    /**
     * Determine if a value is a UUID string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function validUuid($value)
    {
        return is_string($value) &&
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
