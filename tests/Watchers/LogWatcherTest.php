<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\LogWatcher;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use stdClass;

#[WithConfig('logging.default', 'syslog')]
class LogWatcherTest extends FeatureTestCase
{
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
    #[DataProvider('logLevelProvider')]
    #[WithConfig('telescope.watchers', [
        LogWatcher::class => true,
    ])]
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
    #[DataProvider('logLevelProvider')]
    #[WithConfig(['telescope.watchers', [
        LogWatcher::class => [
            'enabled' => true,
            'level' => 'error',
        ],
    ]])]
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
    #[DataProvider('logLevelProvider')]
    #[WithConfig('telescope.watchers', [
        LogWatcher::class => [
            'level' => 'debug',
        ],
    ])]
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
    #[DataProvider('logLevelProvider')]
    #[WithConfig('telescope.watchers', [
        LogWatcher::class => false,
    ])]
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
    #[DataProvider('logLevelProvider')]
    #[WithConfig('telescope.watchers', [
        LogWatcher::class => [
            'enabled' => false,
            'level' => 'error',
        ],
    ])]
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

    #[WithConfig('telescope.watchers', [LogWatcher::class => true])]
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

    public static function interpolationProvider()
    {
        $stringableObject = new class
        {
            public function __toString()
            {
                return 'Stringable Object';
            }
        };

        return [
            'all placeholders replaced' => [
                'User {id} created: {name}.',
                ['id' => 123, 'name' => 'Jill Valentine'],
                'User 123 created: Jill Valentine.',
            ],
            'some placeholders not replaced' => [
                'User {id} created: {name}.',
                ['id' => 456],
                'User 456 created: {name}.',
            ],
            'non-stringable object value' => [
                'Data: {data}, User: {user_id}.',
                ['data' => new stdClass(), 'user_id' => 789],
                'Data: {data}, User: 789.',
            ],
            'array value' => [
                'Request data: {payload}.',
                ['payload' => ['a' => 1, 'b' => 2]],
                'Request data: {payload}.',
            ],
            'stringable object value' => [
                'Object: {obj}.',
                ['obj' => $stringableObject],
                'Object: Stringable Object.',
            ],
            'no placeholders present in context' => [
                'Message with {unprovided} placeholder.',
                ['some_other_key' => 'value'],
                'Message with {unprovided} placeholder.',
            ],
            'null value' => [
                'Value is {value}.',
                ['value' => null],
                'Value is {value}.',
            ],
        ];
    }

    /**
     * @dataProvider interpolationProvider
     */
    #[DataProvider('interpolationProvider')]
    #[WithConfig('telescope.watchers', [
        LogWatcher::class => [
            'enabled' => true,
            'level' => 'info',
        ],
    ])]
    public function test_log_watcher_interpolates_message($message, $context, $expectedMessage)
    {
        $logger = $this->app->get(LoggerInterface::class);

        $logger->info($message, $context);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::LOG, $entry->type);
        $this->assertSame('info', $entry->content['level']);
        $this->assertSame($expectedMessage, $entry->content['message']);
    }
}
