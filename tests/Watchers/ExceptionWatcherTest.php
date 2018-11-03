<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ExceptionWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');

        $app->get('config')->set('telescope.watchers', [
            ExceptionWatcher::class => true,
        ]);
    }

    public function test_exception_watcher_register_entries()
    {
        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new BananaException('Something went bananas.');

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        self::assertSame(EntryType::EXCEPTION, $entry->type);
        self::assertSame(BananaException::class, $entry->content['class']);
        self::assertSame(__FILE__, $entry->content['file']);
        self::assertSame(28, $entry->content['line']);
        self::assertSame('Something went bananas.', $entry->content['message']);
        self::assertArrayHasKey('trace', $entry->content);
    }
}

class BananaException extends Exception
{
}
