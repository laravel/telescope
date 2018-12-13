<?php

namespace Laravel\Telescope\Tests\Watchers;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Watchers\LogWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;

class LogWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');

        $app->get('config')->set('telescope.watchers', [
            LogWatcher::class => true,
        ]);
    }

    public function logLevelProvider()
    {
        return [
            [LogLevel::EMERGENCY],
            [LogLevel::ALERT],
            [LogLevel::CRITICAL],
            [LogLevel::ERROR],
            [LogLevel::WARNING],
            [LogLevel::NOTICE],
            [LogLevel::INFO],
            [LogLevel::DEBUG],
        ];
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function test_log_watcher_registers_entry_for_any_level($level)
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->$level("Logging Level [$level].", [
            'user' => 'Claire Redfield',
            'role' => 'Zombie Hunter',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::LOG, $entry->type);
        $this->assertSame($level, $entry->content['level']);
        $this->assertSame("Logging Level [$level].", $entry->content['message']);
        $this->assertSame('Claire Redfield', $entry->content['context']['user']);
        $this->assertSame('Zombie Hunter', $entry->content['context']['role']);
    }

    public function test_log_watcher_registers_entry_with_exception_key()
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->error('Some message', [
            'exception' => 'Some error message',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::LOG, $entry->type);
        $this->assertSame('error', $entry->content['level']);
        $this->assertSame('Some message', $entry->content['message']);
        $this->assertSame('Some error message', $entry->content['context']['exception']);
    }
}
