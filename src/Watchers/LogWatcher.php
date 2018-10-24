<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Arr;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Log\Events\MessageLogged;

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
        if (isset($event->context['exception'])) {
            return;
        }

        Telescope::recordLog(
            IncomingEntry::make([
                'level' => $event->level,
                'message' => $event->message,
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
}
