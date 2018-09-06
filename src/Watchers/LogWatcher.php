<?php

namespace Laravel\Telescope\Watchers;

use Throwable;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Log\Events\MessageLogged;

class LogWatcher extends Watcher
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
        if (isset($event->context['exception'])) {
            return $this->recordException($event);
        }

        $output = [
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];

        Telescope::recordLogEntry(
            IncomingEntry::make([
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ])->tags($this->extractTagsFromEvent($event))
        );
    }

    /**
     * Record a new exception.
     *
     * @param \Illuminate\Log\Events\MessageLogged $event
     * @return void
     */
    protected function recordException(MessageLogged $event)
    {
        $exception = $event->context['exception'];

        Telescope::recordException(
            IncomingEntry::make([
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine() - 1,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
                'line_preview' => $this->formatLinePreview($exception),
            ])->tags($this->extractTagsFromEvent($event))
        );
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


    /**
     * Extract tags from the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged $event
     * @return array
     */
    private function extractTagsFromEvent($event)
    {
        return $event->context['telescope_tags'] ?? [];
    }
}
