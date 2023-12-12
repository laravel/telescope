<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\LogWatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');

        $config = match (method_exists($this, 'name') ? $this->name() : $this->getName(false)) {
            'test_log_watcher_registers_entry_for_any_level_by_default' => true,
            'test_log_watcher_only_registers_entries_for_the_specified_error_level_priority' => [
                'enabled' => true,
                'level' => 'error',
            ],
            'test_log_watcher_only_registers_entries_for_the_specified_debug_level_priority' => [
                'level' => 'debug',
            ],
            'test_log_watcher_do_not_registers_entry_when_disabled_on_the_boolean_format' => false,
            'test_log_watcher_do_not_registers_entry_when_disabled_on_the_array_format' => [
                'enabled' => false,
                'level' => 'error',
            ],
            'test_log_watcher_registers_entry_with_exception_key' => true,
        };

        $app->get('config')->set('telescope.watchers', [
            LogWatcher::class => $config,
        ]);
    }

    public static function logLevelProvider()
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
    public function test_log_watcher_registers_entry_for_any_level_by_default($level)
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

    /**
     * @dataProvider logLevelProvider
     */
    public function test_log_watcher_only_registers_entries_for_the_specified_error_level_priority($level)
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->$level("Logging Level [$level].", [
            'user' => 'Claire Redfield',
            'role' => 'Zombie Hunter',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        if (in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR])) {
            $this->assertSame(EntryType::LOG, $entry->type);
            $this->assertSame($level, $entry->content['level']);
            $this->assertSame("Logging Level [$level].", $entry->content['message']);
            $this->assertSame('Claire Redfield', $entry->content['context']['user']);
            $this->assertSame('Zombie Hunter', $entry->content['context']['role']);
        } else {
            $this->assertNull($entry);
        }
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function test_log_watcher_only_registers_entries_for_the_specified_debug_level_priority($level)
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

    /**
     * @dataProvider logLevelProvider
     */
    public function test_log_watcher_do_not_registers_entry_when_disabled_on_the_boolean_format($level)
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->$level("Logging Level [$level].", [
            'user' => 'Claire Redfield',
            'role' => 'Zombie Hunter',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNull($entry);
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function test_log_watcher_do_not_registers_entry_when_disabled_on_the_array_format($level)
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->$level("Logging Level [$level].", [
            'user' => 'Claire Redfield',
            'role' => 'Zombie Hunter',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNull($entry);
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
