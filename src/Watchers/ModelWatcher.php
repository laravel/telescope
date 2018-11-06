<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;

class ModelWatcher extends Watcher
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
        if (! $this->shouldRecord($event)) {
            return;
        }

        $model = get_class($data[0]).':'.implode('_', (array) $data[0]->getKey());

        $changes = $data[0]->getChanges();

        Telescope::recordModelEvent(IncomingEntry::make(array_filter([
            'action' => $this->action($event),
            'model' => $model,
            'changes' => empty($changes) ? null : $changes,
        ]))->tags([$model]));
    }

    /**
     * Extract the Eloquent action from the given event.
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
     * Determine if the Eloquent event should be recorded.
     *
     * @param  string  $eventName
     * @return bool
     */
    private function shouldRecord($eventName)
    {
        return Str::is([
            '*created*', '*updated*', '*restored*', '*deleted*',
        ], $eventName);
    }
}
