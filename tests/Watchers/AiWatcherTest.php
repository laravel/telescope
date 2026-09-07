<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\AiWatcher;
use RuntimeException;

class AiWatcherTest extends FeatureTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Telescope::$updatesQueue = [];
    }

    public function test_prompting_agent_records_running_ai_entry()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $entry = $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0000-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $this->assertCount(1, Telescope::$entriesQueue);
        $this->assertSame($entry, Telescope::$entriesQueue[0]);
        $this->assertSame($invocationId, $entry->uuid);
        $this->assertSame($invocationId, $entry->familyHash);
        $this->assertSame(EntryType::AI, $entry->type);
        $this->assertSame('running', $entry->content['status']);
        $this->assertSame($invocationId, $entry->content['invocation_id']);
        $this->assertFalse($entry->content['streaming']);
        $this->assertSame(AiWatcherAgentFake::class, $entry->content['agent']);
        $this->assertSame('openai', $entry->content['provider']);
        $this->assertSame('gpt-test', $entry->content['model']);
        $this->assertSame([], $entry->content['steps']);
        $this->assertSame([], $entry->content['tools']);
        $this->assertSame([], $entry->content['failovers']);
        $this->assertSame([], $entry->content['approvals']);
        $this->assertSame(0, $entry->content['step_count']);
        $this->assertSame(0, $entry->content['tool_count']);
        $this->assertSame(0, $entry->content['failover_count']);
        $this->assertSame(0, $entry->content['approval_count']);
        $this->assertArrayNotHasKey('prompt', $entry->content);
    }

    public function test_streaming_agent_records_running_ai_entry()
    {
        Telescope::startRecording(false);

        $entry = (new AiWatcher)->recordStreamingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0001-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $this->assertSame($entry, Telescope::$entriesQueue[0]);
        $this->assertSame($invocationId, $entry->uuid);
        $this->assertTrue($entry->content['streaming']);
    }

    public function test_ai_run_start_uses_generated_telescope_uuid_when_invocation_id_is_not_a_uuid()
    {
        Telescope::startRecording(false);

        $entry = (new AiWatcher)->recordPromptingAgent(new AiWatcherAgentEventFake(
            'not-a-uuid',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: 'not-a-uuid')
        ));

        $this->assertNotSame('not-a-uuid', $entry->uuid);
        $this->assertSame('not-a-uuid', $entry->familyHash);
        $this->assertSame('not-a-uuid', $entry->content['invocation_id']);
    }

    public function test_ai_run_start_ignores_duplicate_invocation_ids()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $event = new AiWatcherAgentEventFake(
            '01992a3a-0002-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: '01992a3a-0002-7000-8000-000000000000')
        );

        $this->assertNotNull($watcher->recordPromptingAgent($event));
        $this->assertNull($watcher->recordPromptingAgent($event));
        $this->assertCount(1, Telescope::$entriesQueue);
    }

    public function test_agent_prompted_updates_running_ai_entry()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0003-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordAgentPrompted(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId)
        ));

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame($invocationId, $update->uuid);
        $this->assertSame(EntryType::AI, $update->type);
        $this->assertSame('completed', $update->changes['status']);
        $this->assertSame('stop', $update->changes['finish_reason']);
        $this->assertSame(['prompt_tokens' => 10], $update->changes['usage']);
        $this->assertSame(2, $update->changes['step_count']);
        $this->assertSame(1, $update->changes['tool_count']);
        $this->assertSame(0, $update->changes['pending_approval_count']);
        $this->assertArrayHasKey('exception', $update->changes);
        $this->assertNull($update->changes['exception']);
        $this->assertSame('completed', $update->changes['status']);
        $this->assertArrayNotHasKey('text', $update->changes['response']);
        $this->assertArrayNotHasKey('raw', $update->changes['response']);
    }

    public function test_agent_streamed_updates_running_ai_entry_waiting_for_approval()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordStreamingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0004-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordAgentStreamed(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId, steps: [], pendingApprovals: ['approval'], events: [
                new AiWatcherStreamEndEventFake('stop'),
            ])
        ));

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame('waiting_for_approval', $update->changes['status']);
        $this->assertSame('stop', $update->changes['finish_reason']);
        $this->assertSame(1, $update->changes['pending_approval_count']);
    }

    public function test_uuid_shaped_terminal_events_can_update_without_local_start_map()
    {
        Telescope::startRecording(false);

        $update = (new AiWatcher)->recordAgentPrompted(new AiWatcherTerminalEventFake(
            $invocationId = '01992a3a-0006-7000-8000-000000000000',
            new AiWatcherResponseFake($invocationId)
        ));

        $this->assertSame($invocationId, $update->uuid);
    }

    public function test_terminal_updates_use_mapped_uuid_for_non_uuid_invocation_ids()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $entry = $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            'not-a-uuid',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: 'not-a-uuid')
        ));

        $update = $watcher->recordAgentPrompted(new AiWatcherTerminalEventFake(
            'not-a-uuid',
            new AiWatcherResponseFake('not-a-uuid')
        ));

        $this->assertSame($entry->uuid, $update->uuid);
    }

    public function test_agent_failed_updates_running_ai_entry()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0005-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordAgentFailed(new AiWatcherFailedEventFake(
            $invocationId,
            new RuntimeException('Provider failed', 500)
        ));

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame('failed', $update->changes['status']);
        $this->assertSame(RuntimeException::class, $update->changes['exception']['class']);
        $this->assertArrayNotHasKey('message', $update->changes['exception']);
        $this->assertSame(500, $update->changes['exception']['code']);
        $this->assertSame(['failed'], $update->tagsChanges['added']);
    }

    public function test_terminal_events_clear_cached_invocation_state()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $event = new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0022-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        );

        $this->assertNotNull($watcher->recordPromptingAgent($event));
        $watcher->recordAgentPrompted(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId)
        ));
        $this->assertNotNull($watcher->recordPromptingAgent($event));

        $failedEvent = new AiWatcherAgentEventFake(
            $failedInvocationId = '01992a3a-0023-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $failedInvocationId)
        );

        $this->assertNotNull($watcher->recordPromptingAgent($failedEvent));
        $watcher->recordAgentFailed(new AiWatcherFailedEventFake(
            $failedInvocationId,
            new RuntimeException('Provider failed')
        ));
        $this->assertNotNull($watcher->recordPromptingAgent($failedEvent));
    }

    public function test_starting_step_appends_running_step_summary()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0007-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId));

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame($invocationId, $update->uuid);
        $this->assertSame(1, $update->changes['step_count']);
        $this->assertSame([
            'step_number' => 1,
            'agent' => AiWatcherAgentFake::class,
            'provider' => 'openai',
            'model' => 'gpt-test',
            'final' => false,
            'status' => 'running',
            'message_count' => 2,
        ], $update->changes['steps'][0]);
    }

    public function test_step_completed_updates_existing_step_summary()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0008-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));
        $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId));

        $update = $watcher->recordStepCompleted(new AiWatcherStepCompletedEventFake($invocationId));
        $step = $update->changes['steps'][0];

        $this->assertSame($update, Telescope::$updatesQueue[1]);
        $this->assertSame(1, $update->changes['step_count']);
        $this->assertSame('completed', $step['status']);
        $this->assertSame(123.4, $step['duration']);
        $this->assertSame('stop', $step['finish_reason']);
        $this->assertSame(['prompt_tokens' => 10], $step['usage']);
        $this->assertSame(['provider' => 'openai', 'model' => 'gpt-test'], $step['meta']);
        $this->assertSame(1, $step['tool_call_count']);
        $this->assertTrue($step['has_structured']);
        $this->assertArrayNotHasKey('text', $step);
        $this->assertArrayNotHasKey('provider_content_blocks', $step);
    }

    public function test_steps_are_sorted_by_step_number()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0010-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId, stepNumber: 2));
        $update = $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId, stepNumber: 1));

        $this->assertSame([1, 2], array_column($update->changes['steps'], 'step_number'));
    }

    public function test_lifecycle_summaries_are_limited()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher([
            'max_steps' => 1,
            'max_tools' => 1,
            'max_failovers' => 1,
            'max_approvals' => 1,
        ]);
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0024-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $stepUpdate = $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId, stepNumber: 1));
        $stepUpdate = $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId, stepNumber: 2));
        $toolUpdate = $watcher->recordInvokingTool(new AiWatcherToolEventFake($invocationId, toolInvocationId: 'first-tool'));
        $toolUpdate = $watcher->recordInvokingTool(new AiWatcherToolEventFake($invocationId, toolInvocationId: 'second-tool'));
        $failoverUpdate = $watcher->recordAgentFailedOver(new AiWatcherFailedOverEventFake($invocationId));
        $failoverUpdate = $watcher->recordAgentFailedOver(new AiWatcherFailedOverEventFake($invocationId, model: 'gpt-next'));
        $approvalUpdate = $watcher->recordToolApprovalRequested(new AiWatcherApprovalRequestedEventFake($invocationId));
        $approvalUpdate = $watcher->recordToolApprovalResolved(new AiWatcherApprovalResolvedEventFake($invocationId));

        $this->assertSame([2], array_column($stepUpdate->changes['steps'], 'step_number'));
        $this->assertSame(2, $stepUpdate->changes['step_count']);
        $this->assertSame(['second-tool'], array_column($toolUpdate->changes['tools'], 'id'));
        $this->assertSame(2, $toolUpdate->changes['tool_count']);
        $this->assertSame(['gpt-next'], array_column($failoverUpdate->changes['failovers'], 'model'));
        $this->assertSame(2, $failoverUpdate->changes['failover_count']);
        $this->assertSame(['resolved'], array_column($approvalUpdate->changes['approvals'], 'status'));
        $this->assertSame(2, $approvalUpdate->changes['approval_count']);
    }

    public function test_step_updates_use_mapped_uuid_for_non_uuid_invocation_ids()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $entry = $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            'not-a-uuid',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: 'not-a-uuid')
        ));

        $update = $watcher->recordStartingStep(new AiWatcherStepEventFake('not-a-uuid'));

        $this->assertSame($entry->uuid, $update->uuid);
    }

    public function test_terminal_completion_does_not_reset_cached_step_count()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0011-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));
        $watcher->recordStartingStep(new AiWatcherStepEventFake($invocationId));

        $update = $watcher->recordAgentStreamed(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId, steps: [])
        ));

        $this->assertSame(1, $update->changes['step_count']);
    }

    public function test_terminal_completion_does_not_reset_cached_tool_count()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0015-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));
        $watcher->recordInvokingTool(new AiWatcherToolEventFake($invocationId));

        $update = $watcher->recordAgentStreamed(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId, toolCalls: [])
        ));

        $this->assertSame(1, $update->changes['tool_count']);
    }

    public function test_step_failed_updates_step_summary_with_exception()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher(['content' => true]);
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0009-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordStepFailed(new AiWatcherStepFailedEventFake($invocationId));
        $step = $update->changes['steps'][0];

        $this->assertSame('failed', $step['status']);
        $this->assertSame(55.5, $step['duration']);
        $this->assertSame(RuntimeException::class, $step['exception']['class']);
        $this->assertSame('Step failed', $step['exception']['message']);
    }

    public function test_invoking_tool_appends_running_tool_summary()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0012-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordInvokingTool(new AiWatcherToolEventFake($invocationId));
        $tool = $update->changes['tools'][0];

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame(1, $update->changes['tool_count']);
        $this->assertSame('tool-invocation-id', $tool['id']);
        $this->assertSame(AiWatcherAgentFake::class, $tool['agent']);
        $this->assertSame('lookup_weather', $tool['tool']);
        $this->assertSame(AiWatcherNamedToolFake::class, $tool['tool_class']);
        $this->assertSame('running', $tool['status']);
        $this->assertArrayNotHasKey('arguments', $tool);
    }

    public function test_tool_invoked_updates_existing_tool_summary()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher([
            'tool_arguments' => true,
            'tool_results' => true,
            'hidden' => ['api_key'],
        ]);
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0013-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));
        $watcher->recordInvokingTool(new AiWatcherToolEventFake($invocationId));

        $update = $watcher->recordToolInvoked(new AiWatcherToolInvokedEventFake($invocationId));
        $tool = $update->changes['tools'][0];

        $this->assertSame($update, Telescope::$updatesQueue[1]);
        $this->assertSame(1, $update->changes['tool_count']);
        $this->assertSame('completed', $tool['status']);
        $this->assertSame(45.6, $tool['duration']);
        $this->assertSame('********', $tool['arguments']['api_key']);
        $this->assertSame('sunny', $tool['result']['forecast']);
    }

    public function test_tool_failed_updates_tool_summary_with_exception()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher(['content' => true]);
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0014-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordToolFailed(new AiWatcherToolFailedEventFake($invocationId));
        $tool = $update->changes['tools'][0];

        $this->assertSame('failed', $tool['status']);
        $this->assertSame(12.3, $tool['duration']);
        $this->assertSame(RuntimeException::class, $tool['exception']['class']);
        $this->assertSame('Tool failed', $tool['exception']['message']);
    }

    public function test_tool_updates_use_mapped_uuid_for_non_uuid_invocation_ids()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $entry = $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            'not-a-uuid',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: 'not-a-uuid')
        ));

        $update = $watcher->recordInvokingTool(new AiWatcherToolEventFake('not-a-uuid'));

        $this->assertSame($entry->uuid, $update->uuid);
    }

    public function test_agent_failed_over_appends_safe_failover_summary()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0016-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordAgentFailedOver(new AiWatcherFailedOverEventFake($invocationId));
        $failover = $update->changes['failovers'][0];

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame(1, $update->changes['failover_count']);
        $this->assertSame('failed_over', $failover['status']);
        $this->assertSame(AiWatcherAgentFake::class, $failover['agent']);
        $this->assertSame('openai', $failover['provider']);
        $this->assertSame('gpt-test', $failover['model']);
        $this->assertSame(RuntimeException::class, $failover['exception']['class']);
        $this->assertArrayNotHasKey('message', $failover['exception']);
    }

    public function test_tool_approval_requested_appends_safe_checkpoint()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0017-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordToolApprovalRequested(new AiWatcherApprovalRequestedEventFake($invocationId));
        $approval = $update->changes['approvals'][0];
        $pendingApproval = $approval['pending_approvals'][0];

        $this->assertSame($update, Telescope::$updatesQueue[0]);
        $this->assertSame(1, $update->changes['approval_count']);
        $this->assertSame(1, $update->changes['pending_approval_count']);
        $this->assertSame('requested', $approval['status']);
        $this->assertSame(AiWatcherAgentFake::class, $approval['agent']);
        $this->assertSame('conversation-id', $approval['conversation_id']);
        $this->assertSame(AiWatcherConversationUserFake::class, $approval['conversation_user']['class']);
        $this->assertSame(123, $approval['conversation_user']['id']);
        $this->assertSame(1, $approval['pending_approval_count']);
        $this->assertSame('approval-id', $pendingApproval['id']);
        $this->assertSame('lookup_weather', $pendingApproval['name']);
        $this->assertArrayNotHasKey('arguments', $pendingApproval);
        $this->assertArrayNotHasKey('reason', $pendingApproval);
    }

    public function test_tool_approval_resolved_appends_safe_checkpoint()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0018-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $update = $watcher->recordToolApprovalResolved(new AiWatcherApprovalResolvedEventFake($invocationId));
        $approval = $update->changes['approvals'][0];
        $toolResult = $approval['tool_results'][0];

        $this->assertSame(1, $update->changes['approval_count']);
        $this->assertSame(0, $update->changes['pending_approval_count']);
        $this->assertSame('resolved', $approval['status']);
        $this->assertSame(1, $approval['tool_result_count']);
        $this->assertSame('tool-result-id', $toolResult['id']);
        $this->assertSame('lookup', $toolResult['name']);
        $this->assertSame('result-id', $toolResult['result_id']);
        $this->assertFalse($toolResult['denied']);
        $this->assertArrayNotHasKey('arguments', $toolResult);
        $this->assertArrayNotHasKey('result', $toolResult);
    }

    public function test_approval_payloads_are_opt_in_and_redacted()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher([
            'content' => true,
            'tool_arguments' => true,
            'tool_results' => true,
            'hidden' => ['api_key'],
        ]);
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0019-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));

        $requested = $watcher->recordToolApprovalRequested(new AiWatcherApprovalRequestedEventFake($invocationId));
        $resolved = $watcher->recordToolApprovalResolved(new AiWatcherApprovalResolvedEventFake($invocationId));

        $pendingApproval = $requested->changes['approvals'][0]['pending_approvals'][0];
        $toolResult = $resolved->changes['approvals'][1]['tool_results'][0];

        $this->assertSame('********', $pendingApproval['arguments']['api_key']);
        $this->assertSame('Needs approval', $pendingApproval['reason']);
        $this->assertSame('********', $toolResult['arguments']['api_key']);
        $this->assertSame('ok', $toolResult['result']['status']);
    }

    public function test_approval_updates_use_mapped_uuid_for_non_uuid_invocation_ids()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $entry = $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            'not-a-uuid',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: 'not-a-uuid')
        ));

        $update = $watcher->recordToolApprovalRequested(new AiWatcherApprovalRequestedEventFake('not-a-uuid'));

        $this->assertSame($entry->uuid, $update->uuid);
    }

    public function test_terminal_completion_preserves_cached_failover_and_approval_counts()
    {
        Telescope::startRecording(false);

        $watcher = new AiWatcher;
        $watcher->recordPromptingAgent(new AiWatcherAgentEventFake(
            $invocationId = '01992a3a-0020-7000-8000-000000000000',
            new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: $invocationId)
        ));
        $watcher->recordAgentFailedOver(new AiWatcherFailedOverEventFake($invocationId));
        $watcher->recordToolApprovalRequested(new AiWatcherApprovalRequestedEventFake($invocationId));

        $update = $watcher->recordAgentStreamed(new AiWatcherTerminalEventFake(
            $invocationId,
            new AiWatcherResponseFake($invocationId, pendingApprovals: [])
        ));

        $this->assertSame(1, $update->changes['failover_count']);
        $this->assertSame(1, $update->changes['approval_count']);
        $this->assertSame(1, $update->changes['pending_approval_count']);
        $this->assertSame('waiting_for_approval', $update->changes['status']);
    }

    public function test_ai_watcher_registers_available_ai_events()
    {
        $events = new \ReflectionProperty(AiWatcher::class, 'events');

        if (PHP_VERSION_ID < 80500) {
            $events->setAccessible(true);
        }

        $originalEvents = $events->getValue();

        try {
            $events->setValue(null, [
                AiWatcherDummyEvent::class,
                'Laravel\\Ai\\Events\\MissingEvent',
            ]);

            $originalListenersCount = count($this->app['events']->getListeners(AiWatcherDummyEvent::class));
            $originalMissingEventListenersCount = count($this->app['events']->getListeners('Laravel\\Ai\\Events\\MissingEvent'));

            (new AiWatcher)->register($this->app);

            $this->assertCount($originalListenersCount + 1, $this->app['events']->getListeners(AiWatcherDummyEvent::class));
            $this->assertCount($originalMissingEventListenersCount, $this->app['events']->getListeners('Laravel\\Ai\\Events\\MissingEvent'));
        } finally {
            $events->setValue(null, $originalEvents);
        }
    }

    public function test_ai_watcher_does_not_register_or_record_when_disabled()
    {
        $events = new \ReflectionProperty(AiWatcher::class, 'events');
        $watchers = new \ReflectionProperty(Telescope::class, 'watchers');
        $registerWatchers = new \ReflectionMethod(Telescope::class, 'registerWatchers');

        if (PHP_VERSION_ID < 80500) {
            $events->setAccessible(true);
            $watchers->setAccessible(true);
            $registerWatchers->setAccessible(true);
        }

        $originalEvents = $events->getValue();
        $originalWatchers = $watchers->getValue();

        try {
            $events->setValue(null, [
                AiWatcherAgentEventFake::class => 'recordPromptingAgent',
            ]);
            $watchers->setValue(null, []);
            $this->app['config']->set('telescope.watchers', [
                AiWatcher::class => [
                    'enabled' => false,
                ],
            ]);

            $registerWatchers->invoke(null, $this->app);

            $this->assertFalse(Telescope::hasWatcher(AiWatcher::class));

            Telescope::startRecording(false);

            $this->app['events']->dispatch(new AiWatcherAgentEventFake(
                '01992a3a-0021-7000-8000-000000000000',
                new AiWatcherPromptFake(prompt: 'hidden prompt', invocationId: '01992a3a-0021-7000-8000-000000000000')
            ));

            $this->assertSame([], array_values(array_filter(
                Telescope::$entriesQueue,
                fn ($entry) => $entry->type === EntryType::AI
            )));
        } finally {
            $events->setValue(null, $originalEvents);
            $watchers->setValue(null, $originalWatchers);
        }
    }

    public function test_prompt_summary_omits_content_by_default()
    {
        $summary = (new ExposesAiWatcherHelpers)->promptSummary(new AiWatcherPromptFake(
            prompt: 'secret prompt',
            attachments: ['first', 'second'],
        ));

        $this->assertArrayNotHasKey('prompt', $summary);
        $this->assertSame(2, $summary['attachment_count']);
        $this->assertSame(AiWatcherAgentFake::class, $summary['agent']);
        $this->assertSame('openai', $summary['provider']);
    }

    public function test_opted_in_content_is_redacted_and_size_limited()
    {
        $watcher = new ExposesAiWatcherHelpers([
            'content' => true,
            'size_limit' => 1,
            'hidden' => ['password', 'nested.token'],
        ]);

        $summary = $watcher->promptSummary(new AiWatcherPromptFake(prompt: [
            'message' => 'hello',
            'password' => 'secret',
            'nested' => ['token' => 'abc'],
        ]));

        $this->assertSame('hello', $summary['prompt']['message']);
        $this->assertSame('********', $summary['prompt']['password']);
        $this->assertSame('********', $summary['prompt']['nested']['token']);
        $this->assertSame('Purged By Telescope', $watcher->payload(str_repeat('x', 2000), 'content'));
    }

    public function test_tool_summaries_omit_sensitive_payloads_by_default()
    {
        $watcher = new ExposesAiWatcherHelpers;

        $toolCall = $watcher->toolCallSummary(new AiWatcherToolCallFake);
        $toolResult = $watcher->toolResultSummary(new AiWatcherToolResultFake);

        $this->assertArrayNotHasKey('arguments', $toolCall);
        $this->assertArrayNotHasKey('arguments', $toolResult);
        $this->assertArrayNotHasKey('result', $toolResult);
        $this->assertArrayNotHasKey('reasoning_encrypted_content', $toolCall);
    }

    public function test_tool_payloads_are_opt_in_and_redacted()
    {
        $watcher = new ExposesAiWatcherHelpers([
            'tool_arguments' => true,
            'tool_results' => true,
            'hidden' => ['api_key', 'authorization', 'nested.token'],
        ]);

        $toolCall = $watcher->toolCallSummary(new AiWatcherToolCallFake(arguments: [
            'apiKey' => 'secret',
            'Authorization' => 'bearer',
            'api-key' => 'key',
            'nested' => ['token' => 'abc'],
        ]));
        $toolResult = $watcher->toolResultSummary(new AiWatcherToolResultFake);

        $this->assertSame('********', $toolCall['arguments']['apiKey']);
        $this->assertSame('********', $toolCall['arguments']['Authorization']);
        $this->assertSame('********', $toolCall['arguments']['api-key']);
        $this->assertSame('********', $toolCall['arguments']['nested']['token']);
        $this->assertSame('********', $toolResult['arguments']['api_key']);
        $this->assertSame('ok', $toolResult['result']['status']);
    }

    public function test_step_response_summary_stores_counts_and_safe_metadata()
    {
        $summary = (new ExposesAiWatcherHelpers)->stepResponseSummary(new AiWatcherStepResponseFake);

        $this->assertSame('stop', $summary['finish_reason']);
        $this->assertSame(['prompt_tokens' => 10], $summary['usage']);
        $this->assertSame(['provider' => 'openai', 'model' => 'gpt-test'], $summary['meta']);
        $this->assertSame(1, $summary['tool_call_count']);
        $this->assertSame(1, $summary['pending_approval_count']);
        $this->assertTrue($summary['has_structured']);
        $this->assertArrayNotHasKey('text', $summary);
        $this->assertArrayNotHasKey('raw', $summary);
        $this->assertArrayNotHasKey('provider_content_blocks', $summary);
    }

    public function test_raw_response_payloads_are_opt_in_and_redacted()
    {
        $watcher = new ExposesAiWatcherHelpers([
            'raw' => true,
            'hidden' => ['api_key'],
        ]);
        $response = new AiWatcherResponseFake('invocation-id');
        $response->raw = new AiWatcherRawResponseFake(['api_key' => 'secret', 'result' => 'ok']);

        $summary = $watcher->responseSummary($response);

        $this->assertSame('********', $summary['raw']['api_key']);
        $this->assertSame('ok', $summary['raw']['result']);
    }

    public function test_exception_summary_is_safe()
    {
        $summary = (new ExposesAiWatcherHelpers(['content' => true]))->exceptionSummary(
            new RuntimeException("Invalid \xB1 secret", 123)
        );

        $this->assertSame(RuntimeException::class, $summary['class']);
        $this->assertSame(json_decode('"Invalid \ufffd secret"'), $summary['message']);
        $this->assertSame(123, $summary['code']);
    }
}

class AiWatcherDummyEvent
{
    //
}

class AiWatcherAgentEventFake
{
    public function __construct(
        public $invocationId,
        public $prompt,
    ) {
        //
    }
}

class AiWatcherTerminalEventFake
{
    public function __construct(
        public $invocationId,
        public $response,
    ) {
        //
    }
}

class AiWatcherFailedEventFake
{
    public function __construct(
        public $invocationId,
        public $exception,
    ) {
        //
    }
}

class AiWatcherFailedOverEventFake
{
    public $agent;

    public $provider;

    public $exception;

    public function __construct(
        public $invocationId,
        public $model = 'gpt-test',
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->provider = new AiWatcherProviderFake;
        $this->exception = new RuntimeException('Provider failed over');
    }
}

class AiWatcherApprovalRequestedEventFake
{
    public $agent;

    public $pendingApprovals;

    public $conversationUser;

    public function __construct(
        public $invocationId,
        public $conversationId = 'conversation-id',
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->pendingApprovals = [new AiWatcherPendingApprovalFake];
        $this->conversationUser = new AiWatcherConversationUserFake;
    }
}

class AiWatcherApprovalResolvedEventFake
{
    public $agent;

    public $toolResults;

    public $conversationUser;

    public function __construct(
        public $invocationId,
        public $conversationId = 'conversation-id',
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->toolResults = [new AiWatcherToolResultFake];
        $this->conversationUser = new AiWatcherConversationUserFake;
    }
}

class AiWatcherStepEventFake
{
    public $agent;

    public $provider;

    public function __construct(
        public $invocationId,
        public $stepNumber = 1,
        public $model = 'gpt-test',
        public $isFinalStep = false,
        public $messages = ['first', 'second'],
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->provider = new AiWatcherProviderFake;
    }
}

class AiWatcherStepCompletedEventFake extends AiWatcherStepEventFake
{
    public $response;

    public $time = 123.4;

    public function __construct($invocationId)
    {
        parent::__construct($invocationId);

        $this->response = new AiWatcherStepResponseFake;
    }
}

class AiWatcherStepFailedEventFake extends AiWatcherStepEventFake
{
    public $exception;

    public $time = 55.5;

    public function __construct($invocationId)
    {
        parent::__construct($invocationId);

        $this->exception = new RuntimeException('Step failed');
    }
}

class AiWatcherToolEventFake
{
    public $agent;

    public $tool;

    public function __construct(
        public $invocationId,
        public $toolInvocationId = 'tool-invocation-id',
        public $arguments = ['api_key' => 'secret'],
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->tool = new AiWatcherNamedToolFake;
    }
}

class AiWatcherToolInvokedEventFake extends AiWatcherToolEventFake
{
    public $result = ['forecast' => 'sunny'];

    public $time = 45.6;
}

class AiWatcherToolFailedEventFake extends AiWatcherToolEventFake
{
    public $exception;

    public $time = 12.3;

    public function __construct($invocationId)
    {
        parent::__construct($invocationId);

        $this->exception = new RuntimeException('Tool failed');
    }
}

class AiWatcherPendingApprovalFake
{
    public $id = 'approval-id';

    public $tool = 'lookup_weather';

    public $arguments = ['api_key' => 'secret'];

    public $reason = 'Needs approval';
}

class AiWatcherNamedToolFake
{
    public function name()
    {
        return 'lookup_weather';
    }
}

class ExposesAiWatcherHelpers extends AiWatcher
{
    public function promptSummary(object $prompt)
    {
        return $this->summarizePrompt($prompt);
    }

    public function toolCallSummary(object $toolCall)
    {
        return $this->summarizeToolCall($toolCall);
    }

    public function toolResultSummary(object $toolResult)
    {
        return $this->summarizeToolResult($toolResult);
    }

    public function stepResponseSummary(object $response)
    {
        return $this->summarizeStepResponse($response);
    }

    public function responseSummary(object $response)
    {
        return $this->summarizeResponse($response);
    }

    public function exceptionSummary(\Throwable $exception)
    {
        return $this->summarizeException($exception);
    }

    public function payload($value, $option)
    {
        return $this->safePayload($value, $option);
    }
}

class AiWatcherPromptFake
{
    public $agent;

    public $provider;

    public function __construct(
        public $prompt,
        public $attachments = [],
        public $model = 'gpt-test',
        public $timeout = 30,
        public $invocationId = 'invocation-id',
        public $parentInvocationId = null,
        public $parentToolInvocationId = null,
    ) {
        $this->agent = new AiWatcherAgentFake;
        $this->provider = new AiWatcherProviderFake;
    }
}

class AiWatcherAgentFake
{
    //
}

class AiWatcherProviderFake
{
    public function name()
    {
        return 'openai';
    }
}

class AiWatcherConversationUserFake
{
    public $id = 123;
}

class AiWatcherToolCallFake
{
    public $id = 'tool-call-id';

    public $name = 'lookup';

    public $arguments = ['api_key' => 'secret'];

    public $resultId = 'result-id';

    public $reasoningId = 'reasoning-id';

    public $reasoningEncryptedContent = 'encrypted';

    public function __construct($arguments = null)
    {
        $this->arguments = $arguments ?? $this->arguments;
    }
}

class AiWatcherToolResultFake
{
    public $id = 'tool-result-id';

    public $name = 'lookup';

    public $arguments = ['api_key' => 'secret'];

    public $result = ['status' => 'ok'];

    public $resultId = 'result-id';

    public $denied = false;
}

class AiWatcherStepResponseFake
{
    public $text = 'step text';

    public $toolCalls = ['tool'];

    public $finishReason;

    public $usage;

    public $meta;

    public $structured = ['answer' => true];

    public $providerContentBlocks = [['text' => 'hidden']];

    public $pendingApprovals = ['approval'];

    public function __construct()
    {
        $this->finishReason = new AiWatcherEnumFake('stop');
        $this->usage = new AiWatcherArrayableFake(['prompt_tokens' => 10]);
        $this->meta = new AiWatcherArrayableFake(['provider' => 'openai', 'model' => 'gpt-test']);
    }
}

class AiWatcherEnumFake
{
    public function __construct(public $value)
    {
        //
    }
}

class AiWatcherArrayableFake
{
    public function __construct(public $payload)
    {
        //
    }

    public function toArray()
    {
        return $this->payload;
    }
}

class AiWatcherRawResponseFake
{
    public function __construct(public $payload)
    {
        //
    }

    public function json()
    {
        return $this->payload;
    }
}

class AiWatcherResponseFake
{
    public $usage;

    public $meta;

    public $raw = null;

    public function __construct(
        public $invocationId,
        public $steps = null,
        public $toolCalls = ['tool'],
        public $toolResults = [],
        public $messages = [],
        public $pendingApprovals = [],
        public $text = 'hidden text',
        public $events = [],
    ) {
        $this->usage = new AiWatcherArrayableFake(['prompt_tokens' => 10]);
        $this->meta = new AiWatcherArrayableFake(['provider' => 'openai', 'model' => 'gpt-test']);
        $this->steps ??= [
            new AiWatcherResponseStepFake('tool_calls'),
            new AiWatcherResponseStepFake('stop'),
        ];
    }
}

class AiWatcherStreamEndEventFake
{
    public function __construct(public $reason)
    {
        //
    }
}

class AiWatcherResponseStepFake
{
    public $finishReason;

    public function __construct($finishReason)
    {
        $this->finishReason = new AiWatcherEnumFake($finishReason);
    }
}
