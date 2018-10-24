<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\ExceptionContext;
use Illuminate\Log\Events\MessageLogged;
use Laravel\Telescope\IncomingExceptionEntry;

class ExceptionWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordException']);
    }

    /**
     * Record an exception was logged.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return void
     */
    public function recordException(MessageLogged $event)
    {
        if (! isset($event->context['exception'])) {
            return;
        }

        $exception = $event->context['exception'];

        Telescope::recordException(
            IncomingExceptionEntry::make($exception, [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
                'line_preview' => ExceptionContext::get($exception),
            ])->tags($this->tags($event))
        );
    }

    /**
     * Extract the tags for the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return array
     */
    protected function tags($event)
    {
        return array_merge(ExtractTags::from($event->context['exception']),
            $event->context['telescope'] ?? []
        );
    }
}
