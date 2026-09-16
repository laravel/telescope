<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Ai;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Events\AgentFailedOver;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\Fixtures\Ai\Agent;
use Laravel\Telescope\Tests\Fixtures\Ai\FailoverAgent;
use Laravel\Telescope\Tests\Fixtures\Ai\FailoverException;
use Laravel\Telescope\Tests\Fixtures\Ai\RefundTool;
use Laravel\Telescope\Tests\Fixtures\Ai\ToolAgent;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\AiWatcher;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use RuntimeException;

#[DefineEnvironment('watchAiRuns')]
class AiWatcherTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function getPackageProviders($app)
    {
        // Providers are registered before the environment is defined, so this cannot wait for the skip below...
        return array_merge(
            class_exists(AiServiceProvider::class) ? [AiServiceProvider::class] : [],
            parent::getPackageProviders($app)
        );
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $this->markTestSkippedUnless(class_exists(AiServiceProvider::class), 'The "laravel/ai" composer package is required for this test.');

        parent::defineEnvironment($app);

        $app->make('config')->set([
            'ai.default' => 'openai',
            'ai.providers.openai' => ['driver' => 'openai', 'key' => 'test-key'],
            'ai.providers.anthropic' => ['driver' => 'anthropic', 'key' => 'test-key'],
        ]);
    }

    /**
     * Watch AI runs, and nothing else.
     */
    protected function watchAiRuns($app)
    {
        $app->make('config')->set('telescope.watchers', [
            AiWatcher::class => ['enabled' => true],
        ]);
    }

    /**
     * Opt the watcher into recording prompt, response, and tool content.
     */
    protected function recordContent($app)
    {
        $app->make('config')->set('telescope.watchers', [
            AiWatcher::class => ['enabled' => true, 'content' => true],
        ]);
    }

    /**
     * Opt into recording content, but with a size limit nothing can fit within.
     */
    protected function recordContentWithoutRoom($app)
    {
        $app->make('config')->set('telescope.watchers', [
            AiWatcher::class => ['enabled' => true, 'content' => true, 'size_limit' => 0],
        ]);
    }

    public function test_it_records_a_completed_agent_run()
    {
        Agent::fake(['Sure, I can help with that.']);

        Agent::make()->prompt('How do I refund an order?');

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('completed', $entry->content['status']);
        $this->assertSame(Agent::class, $entry->content['agent']);
        $this->assertSame('openai', $entry->content['provider']);
        $this->assertSame('gpt-5', $entry->content['model']);
        $this->assertFalse($entry->content['streaming']);
        $this->assertSame('stop', $entry->content['finish_reason']);
        $this->assertArrayHasKey('prompt_tokens', $entry->content['usage']);
        $this->assertCount(1, $entry->content['steps']);
        $this->assertSame('completed', $entry->content['steps'][0]['status']);
        $this->assertSame(0, $entry->content['steps'][0]['step']);
        $this->assertSame('openai', $entry->content['steps'][0]['provider']);
        $this->assertSame([], $entry->content['tools']);
        $this->assertNull($entry->content['exception']);

        $this->assertContains(Agent::class, DB::table('telescope_entries_tags')
            ->where('entry_uuid', $entry->getKey())->pluck('tag')->all());
    }

    public function test_it_records_a_streamed_agent_run()
    {
        Agent::fake(['Streamed answer.']);

        iterator_to_array(Agent::make()->stream('How do I refund an order?'));

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('completed', $entry->content['status']);
        $this->assertTrue($entry->content['streaming']);
    }

    public function test_it_does_not_record_prompt_or_response_content_by_default()
    {
        Agent::fake(['Sure, I can help with that.']);

        Agent::make()->prompt('My password is hunter2.');

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertNull($entry->content['prompt']);
        $this->assertNull($entry->content['response']);
        $this->assertNull($entry->content['steps'][0]['text']);
    }

    #[DefineEnvironment('recordContent')]
    public function test_it_records_prompt_and_response_content_when_enabled()
    {
        Agent::fake(['Sure, I can help with that.']);

        Agent::make()->prompt('How do I refund an order?');

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('How do I refund an order?', $entry->content['prompt']);
        $this->assertSame('Sure, I can help with that.', $entry->content['response']);
        $this->assertSame('Sure, I can help with that.', $entry->content['steps'][0]['text']);
    }

    #[DefineEnvironment('recordContentWithoutRoom')]
    public function test_it_purges_content_that_exceeds_the_size_limit()
    {
        Agent::fake([str_repeat('a', 1500)]);

        Agent::make()->prompt(str_repeat('b', 1500));

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('Purged By Telescope', $entry->content['prompt']);
        $this->assertSame('Purged By Telescope', $entry->content['response']);
    }

    #[DefineEnvironment('recordContent')]
    public function test_it_records_tool_invocations()
    {
        ToolAgent::fake([
            new ToolCall('call-1', 'refund-order', ['order' => 123, 'password' => 'hunter2']),
            'The order was refunded.',
        ]);

        ToolAgent::make()->prompt('Refund order 123.');

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('completed', $entry->content['status']);
        $this->assertCount(2, $entry->content['steps']);
        $this->assertCount(1, $entry->content['tools']);

        $tool = $entry->content['tools'][0];

        $this->assertSame('completed', $tool['status']);
        $this->assertSame('refund-order', $tool['tool']);
        $this->assertSame(RefundTool::class, $tool['tool_class']);
        $this->assertSame(123, $tool['arguments']['order']);
        $this->assertSame('********', $tool['arguments']['password']);
        $this->assertSame('Refunded order 123.', $tool['result']);
        $this->assertIsFloat($tool['duration']);
    }

    #[DefineEnvironment('recordContent')]
    public function test_it_records_a_failed_tool_invocation()
    {
        ToolAgent::fake([
            new ToolCall('call-1', 'explode', []),
            'Recovered.',
        ]);

        try {
            ToolAgent::make()->prompt('Explode, please.');
        } catch (RuntimeException) {
            //
        }

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);
        $tool = $entry->content['tools'][0];

        $this->assertSame('failed', $tool['status']);
        $this->assertSame(RuntimeException::class, $tool['exception']['class']);
        $this->assertSame('Tool exploded.', $tool['exception']['message']);
    }

    public function test_it_records_a_failed_agent_run()
    {
        Agent::fake([fn () => throw new RuntimeException('The provider is down.')]);

        try {
            Agent::make()->prompt('How do I refund an order?');
        } catch (RuntimeException) {
            //
        }

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('failed', $entry->content['status']);
        $this->assertSame(RuntimeException::class, $entry->content['exception']['class']);
        $this->assertSame('The provider is down.', $entry->content['exception']['message']);
        $this->assertCount(1, $entry->content['steps']);
        $this->assertSame('failed', $entry->content['steps'][0]['status']);

        $this->assertContains('failed', DB::table('telescope_entries_tags')
            ->where('entry_uuid', $entry->getKey())->pluck('tag')->all());
    }

    public function test_it_records_a_run_that_fails_over_to_another_provider()
    {
        $attempts = 0;

        FailoverAgent::fake([function () use (&$attempts) {
            return ++$attempts === 1
                ? throw new FailoverException('Rate limited.')
                : 'Recovered on the second provider.';
        }]);

        FailoverAgent::make()->prompt('How do I refund an order?');

        $entries = $this->loadTelescopeEntries()->where('type', EntryType::AI);

        $this->assertCount(1, $entries, 'A failover must reuse the entry of the invocation it retries.');

        $entry = $entries->first();

        $this->assertSame('completed', $entry->content['status']);
        $this->assertSame('anthropic', $entry->content['provider']);
        $this->assertCount(1, $entry->content['failovers']);
        $this->assertSame('openai', $entry->content['failovers'][0]['provider']);
        $this->assertSame('Rate limited.', $entry->content['failovers'][0]['exception']['message']);

        $this->assertNotContains('failed', DB::table('telescope_entries_tags')
            ->where('entry_uuid', $entry->getKey())->pluck('tag')->all());
    }

    #[DefineEnvironment('recordContent')]
    public function test_it_records_a_run_paused_for_tool_approval()
    {
        Agent::fake([
            AgentResponse::fakeWithPendingApprovals([
                new PendingApproval('call-1', 'refund-order', ['order' => 123], 'Refunds need a human.'),
            ]),
        ]);

        Agent::make()->prompt('Refund order 123.');

        $entry = $this->loadTelescopeEntries()->firstWhere('type', EntryType::AI);

        $this->assertSame('waiting_for_approval', $entry->content['status']);
        $this->assertCount(1, $entry->content['pending_approvals']);
        $this->assertSame('call-1', $entry->content['pending_approvals'][0]['id']);
        $this->assertSame('refund-order', $entry->content['pending_approvals'][0]['tool']);
        $this->assertSame('Refunds need a human.', $entry->content['pending_approvals'][0]['reason']);
        $this->assertSame(['order' => 123], $entry->content['pending_approvals'][0]['arguments']);
    }

    public function test_it_ignores_events_for_runs_it_did_not_start()
    {
        $watcher = new AiWatcher;

        $this->assertNull($watcher->recordFailedOver(new AgentFailedOver(
            'missing-invocation',
            Agent::make(),
            Ai::textProviderFor(Agent::make(), 'openai'),
            'gpt-5',
            new FailoverException('Rate limited.'),
        )));

        $this->assertEmpty($this->loadTelescopeEntries());
    }
}
