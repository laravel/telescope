<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Context;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\CommandWatcher;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('telescope.watchers', [
    CommandWatcher::class => true,
])]
class CommandWatcherTest extends FeatureTestCase
{
    public function test_command_watcher_register_entry()
    {
        $this->app->get(Kernel::class)->registerCommand(new MyCommand);

        $this->artisan('telescope:test-command');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::COMMAND, $entry->type);
        $this->assertSame('telescope:test-command', $entry->content['command']);
        $this->assertSame(0, $entry->content['exit_code']);
    }

    public function test_command_watcher_registers_context()
    {
        if (! class_exists(Context::class)) {
            $this->markTestSkipped('Context is not available in this version of Laravel.');
        }

        Context::add('user_id', 123);
        Context::add('tenant_id', 'acme');

        $this->app->get(Kernel::class)->registerCommand(new MyCommand);

        $this->artisan('telescope:test-command');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::COMMAND, $entry->type);
        $this->assertArrayHasKey('context', $entry->content);
        $this->assertSame(123, $entry->content['context']['user_id']);
        $this->assertSame('acme', $entry->content['context']['tenant_id']);
    }

    public function test_command_watcher_context_is_null_when_empty()
    {
        if (! class_exists(Context::class)) {
            $this->markTestSkipped('Context is not available in this version of Laravel.');
        }

        $this->app->get(Kernel::class)->registerCommand(new MyCommand);

        $this->artisan('telescope:test-command');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::COMMAND, $entry->type);
        $this->assertNull($entry->content['context'] ?? null);
    }
}

class MyCommand extends Command
{
    protected $signature = 'telescope:test-command';

    public function handle()
    {
        //
    }
}
