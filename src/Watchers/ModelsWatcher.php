<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Events\QueryExecuted;

class ModelsWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen('eloquent.*', [$this, 'recordAction']);
    }

    /**
     * Record an action.
     *
     * @param  string  $event
     * @param  array  $data
     * @return void
     */
    public function recordAction($event, $data)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordModelEvent(IncomingEntry::make([
            'action' => $this->extractAction($event),
            'model' => get_class($data[0]).':'.$data[0]->getKey(),
            'changes' => $data[0]->getChanges(),
        ]));
    }

    /**
     * Determine if the action should be ignored.
     *
     * @param  string  $eventName
     * @return bool
     */
    private function shouldIgnore($eventName)
    {
        return Str::is([
            '*booting*', '*booted*', '*creating*', '*retrieved*', '*updating*',
            '*saving*', '*saved*', '*restoring*', '*deleting*'
        ], $eventName);
    }

    /**
     * Extract action from the given event.
     *
     * @param  string  $event
     * @return mixed
     */
    private function extractAction($event)
    {
        preg_match('/\.(.*):/', $event, $matches);

        return $matches[1];
    }
}
