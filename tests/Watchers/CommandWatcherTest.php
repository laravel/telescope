<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Contracts\Console\Kernel;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\CommandWatcher;
use Laravel\Telescope\Tests\Fixtures\MyCommand;

class CommandWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            CommandWatcher::class => true,
        ]);
    }

    public function test_command_watcher_register_entry()
    {
        $this->app->get(Kernel::class)->registerCommand(new MyCommand);

        $this->artisan('telescope:test-command');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::COMMAND, $entry->type);
        $this->assertSame('telescope:test-command', $entry->content['command']);
        $this->assertSame(0, $entry->content['exit_code']);
    }
}
