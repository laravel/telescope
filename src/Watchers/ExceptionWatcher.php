<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Laravel\Telescope\ExceptionContext;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingExceptionEntry;
use Laravel\Telescope\Telescope;
use Throwable;

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
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        $exception = $event->context['exception'];

        $trace = collect($exception->getTrace())->map(function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        Telescope::recordException(
            IncomingExceptionEntry::make($exception, [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'context' => transform(Arr::except($event->context, ['exception', 'telescope']), function ($context) {
                    return ! empty($context) ? $context : null;
                }),
                'trace' => $trace,
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

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return ! isset($event->context['exception']) ||
            ! $event->context['exception'] instanceof Throwable;
    }
}
