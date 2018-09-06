<?php

namespace Laravel\Telescope\Watchers;

use Throwable;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Log\Events\MessageLogged;

class ExceptionWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordException']);
    }

    /**
     * Record a new exception.
     *
     * @param \Illuminate\Log\Events\MessageLogged $event
     * @return void
     */
    public function recordException(MessageLogged $event)
    {
        if (! isset($event->context['exception'])) {
            return;
        }

        $exception = $event->context['exception'];

        Telescope::recordException(
            IncomingEntry::make([
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
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
        $result = (new Inspector($exception))
                ->getFrames()[0]
                ->getFileLines($exception->getLine() - 10, 20);

        return collect($result)->mapWithKeys(function ($value, $key) {
            return [$key + 1 => $value];
        })->all();
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
