<?php

namespace Laravel\Telescope\Tests\Watchers;

use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\TelescopeException;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use ParseError;
use Throwable;

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
        $this->assertSame(33, $entry->content['line']);
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
        $this->assertSame('syntax error, unexpected end of file', $entry->content['message']);
        $this->assertArrayHasKey('trace', $entry->content);
    }

    public function test_telescope_exception_implementation()
    {
        $handler = $this->app->get(ExceptionHandler::class);

        $context = [
            'data' => Str::random(),
        ];

        $exception = new CustomException('Exception with context.', 1, null, $context);

        $handler->report($exception);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EXCEPTION, $entry->type);
        $this->assertSame(CustomException::class, $entry->content['class']);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(82, $entry->content['line']);
        $this->assertSame('Exception with context.', $entry->content['message']);
        $this->assertArrayHasKey('trace', $entry->content);
        $this->assertArrayHasKey('data', $entry->content['context']);
        $this->assertSame($context['data'], $entry->content['context']['data']);
    }
}

class BananaException extends Exception
{
    //
}

class CustomException extends TelescopeException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null, $context = [])
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }
}
