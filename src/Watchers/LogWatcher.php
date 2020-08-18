<?php

namespace Laravel\Telescope\Watchers;

use Exception;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class LogWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordLog']);
    }

    /**
     * Record a message was logged.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return void
     */
    public function recordLog(MessageLogged $event)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordLog(
            IncomingEntry::make([
                'level' => $event->level,
                'message' => (string) $event->message,
                'context' => Arr::except($event->context, ['telescope']),
            ])->tags($this->tags($event))
        );
    }

    /**
     * Extract tags from the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return array
     */
    private function tags($event)
    {
        return $event->context['telescope'] ?? [];
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return isset($event->context['exception']) &&
            $event->context['exception'] instanceof Exception;
    }
}
