<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Bus\Events\BatchDispatched;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class BatchWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(BatchDispatched::class, [$this, 'recordBatch']);
    }

    /**
     * Record a batch being dispatched.
     *
     * @param  \Illuminate\Bus\Events\BatchDispatched  $event
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    public function recordBatch(BatchDispatched $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $content = array_merge($event->batch->toArray(), [
            'queue' => $event->batch->options['queue'] ?? 'default',
            'connection' => $event->batch->options['connection'] ?? 'default',
            'allowsFailures' => $event->batch->allowsFailures(),
        ]);

        Telescope::recordBatch(
            $entry = IncomingEntry::make(
                $content,
                $event->batch->id
            )->withFamilyHash($event->batch->id)
        );

        return $entry;
    }
}
