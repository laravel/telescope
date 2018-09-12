<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;

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

        $model = get_class($data[0]).':'.$data[0]->getKey();

        Telescope::recordModelEvent(IncomingEntry::make([
            'action' => $this->action($event),
            'model' => $model,
            'changes' => $data[0]->getChanges(),
        ])->tags([$model]));
    }

    /**
     * Extract action from the given event.
     *
     * @param  string  $event
     * @return mixed
     */
    private function action($event)
    {
        preg_match('/\.(.*):/', $event, $matches);

        return $matches[1];
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
}
