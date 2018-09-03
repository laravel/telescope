<?php

namespace Laravel\Telescope\Watchers;

use Throwable;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Illuminate\Log\Events\MessageLogged;

class LogWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordMessage']);
    }

    /**
     * Record a new log message.
     *
     * @param \Illuminate\Log\Events\MessageLogged $event
     * @return void
     */
    public function recordMessage(MessageLogged $event)
    {
        $output = [
            'level' => $event->level,
            'message' => $event->message,
            'exception' => isset($event->context['exception'])
                ? $this->formatException($event->context['exception']) : null,
        ];

        if (! isset($event->context['exception'])) {
            $output['context'] = $event->context;
        }

        Telescope::record(2, $output);
    }

    /**
     * Format the given exception.
     *
     * @param  Throwable $exception
     * @return array
     */
    private function formatException(Throwable $exception)
    {
        return [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'line_preview' => $this->formatLinePreview($exception),
        ];
    }

    /**
     * Format the exception line preview.
     *
     * @param  Throwable $exception
     * @return mixed
     */
    private function formatLinePreview(Throwable $exception)
    {
        return (new Inspector($exception))
            ->getFrames()[0]
            ->getFileLines($exception->getLine() - 10, 20);
    }
}