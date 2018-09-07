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
        $app['events']->listen(MessageLogged::class, [$this, 'recordLog']);
    }

    /**
     * Record a new log message.
     *
     * @param \Illuminate\Log\Events\MessageLogged $event
     * @return void
     */
    public function recordLog(MessageLogged $event)
    {
        if (isset($event->context['exception'])) {
            return;
        }

        Telescope::recordLogEntry(
            IncomingEntry::make([
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ])->tags($this->extractTagsFromEvent($event))
        );
    }


    /**
     * Extract tags from the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged $event
     * @return array
     */
    private function extractTagsFromEvent($event)
    {
        return $event->context['telescope'] ?? [];
    }
}
