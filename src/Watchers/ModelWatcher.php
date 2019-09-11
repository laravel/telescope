<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\FormatModel;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

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
        $app['events']->listen($this->options['events'] ?? 'eloquent.*', [$this, 'recordAction']);
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
        if (! Telescope::isRecording() || ! $this->shouldRecord($event)) {
            return;
        }

        $model = FormatModel::given($data[0]);

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
