<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Arr;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Log\ParsesLogConfiguration;

class LogWatcher extends Watcher
{
    use ParsesLogConfiguration;

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
        if (! Telescope::isRecording() || isset($event->context['exception']) || $this->shouldIgnore($event)) {
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

    /**
     * Abstract function getFallbackChannelName(); must be defined to use the ParsesLogConfiguration Trait.
     *
     * @return void
     */
    protected function getFallbackChannelName()
    {
    }

    /**
     * Determine if the event should be ignored. Checks if log level too low.
     *
     * @param  \Illuminate\Log\Events\MessageLogged $event
     * @return bool
     */
    private function shouldIgnore(MessageLogged $event)
    {
        return $this->level(['level' => $event->level]) < $this->level(['level' => ($this->options['level'] ?? 'debug')]);
    }
}
