<?php

namespace Laravel\Telescope\Tests\Watchers;

use Error;
use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Context;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use Orchestra\Testbench\Attributes\WithConfig;
use ParseError;

#[WithConfig('logging.default', 'syslog')]
#[WithConfig('telescope.watchers', [
    ExceptionWatcher::class => true,
])]
class ExceptionWatcherTest extends FeatureTestCase
{
    public function test_exception_watcher_register_entries()
    {
        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new BananaException('Something went bananas.');

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertSame(BananaException::class, $entry->content['class']);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(26, $entry->content['line']);
        $this->assertSame('Something went bananas.', $entry->content['message']);
        $this->assertArrayHasKey('trace', $entry->content);
    }

    public function test_exception_watcher_register_throwable_entries()
    {
        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new BananaError('Something went bananas.');

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertSame(BananaError::class, $entry->content['class']);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(44, $entry->content['line']);
        $this->assertSame('Something went bananas.', $entry->content['message']);
        $this->assertArrayHasKey('trace', $entry->content);
    }

    public function test_exception_watcher_register_entries_when_eval_failed()
    {
        $handler = $this->app->get(ExceptionHandler::class);

        $exception = null;

        try {
            eval('if (');

            $this->fail('eval() was expected to throw "syntax error, unexpected end of file"');
        } catch (ParseError $e) {
            // PsySH class ExecutionLoopClosure wraps ParseError in an exception.
            $exception = new ErrorException($e->getMessage(), $e->getCode(), 1, $e->getFile(), $e->getLine(), $e);
        }

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertSame(ErrorException::class, $entry->content['class']);
        $this->assertStringContainsString("eval()'d code", $entry->content['file']);
        $this->assertSame(1, $entry->content['line']);

        if (\PHP_VERSION_ID < 80000) {
            $this->assertSame('syntax error, unexpected end of file', $entry->content['message']);
        } else {
            $this->assertSame("Unclosed '('", $entry->content['message']);
        }

        $this->assertArrayHasKey('trace', $entry->content);
    }

    public function test_exception_watcher_registers_request_context()
    {
        if (! class_exists(Context::class)) {
            $this->markTestSkipped('Context is not available in this version of Laravel.');
        }

        Context::add('user_id', 789);
        Context::add('session_id', 'sess-abc');

        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new BananaException('Context test exception.');

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertArrayHasKey('request_context', $entry->content);
        $this->assertSame(789, $entry->content['request_context']['user_id']);
        $this->assertSame('sess-abc', $entry->content['request_context']['session_id']);
    }

    public function test_exception_watcher_request_context_is_null_when_empty()
    {
        if (! class_exists(Context::class)) {
            $this->markTestSkipped('Context is not available in this version of Laravel.');
        }

        $handler = $this->app->get(ExceptionHandler::class);

        $exception = new BananaException('Empty context test exception.');

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertNull($entry->content['request_context'] ?? null);
    }
}

class BananaException extends Exception
{
    //
}

class BananaError extends Error
{
    //
}
