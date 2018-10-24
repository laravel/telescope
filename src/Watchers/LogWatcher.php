<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Arr;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

class LogWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register(Application $app)
    {
        $app->make(Dispatcher::class)->listen(MessageLogged::class, [$this, 'recordLog']);
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
    private function tags(MessageLogged $event)
    {
        return $event->context['telescope'] ?? [];
    }
}
