<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Telescope\Watchers\ExceptionWatcher;

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

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertSame(BananaException::class, $entry->content['class']);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(28, $entry->content['line']);
        $this->assertSame('Something went bananas.', $entry->content['message']);
        $this->assertArrayHasKey('trace', $entry->content);
    }
}

class BananaException extends Exception
{
    //
}
