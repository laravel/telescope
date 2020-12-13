<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\FormatModel;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;

class ModelWatcher extends Watcher
{
    /**
     * Telescope entries to store the count model hydrations.
     *
     * @var array
     */
    public $hydrationEntries = [];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen($this->options['events'] ?? 'eloquent.*', [$this, 'recordAction']);

        Telescope::afterStoring(function () {
            $this->flush();
        });
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

        if (Str::is('*retrieved*', $event)) {
            $this->recordHydrations($data);

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
     * Record model hydrations.
     *
     * @param  array  $data
     * @return void
     */
    public function recordHydrations($data)
    {
        if (! ($this->options['hydrations'] ?? false)
            || ! $this->shouldRecordHydration($modelClass = get_class($data[0]))) {
            return;
        }

        if (! isset($this->hydrationEntries[$modelClass])) {
            $this->hydrationEntries[$modelClass] = IncomingEntry::make([
                'action' => 'retrieved',
                'model' => $modelClass,
                'count' => 1,
            ])->tags([$modelClass]);

            Telescope::recordModelEvent($this->hydrationEntries[$modelClass]);
        } else {
            $this->hydrationEntries[$modelClass]->content['count']++;
        }
    }

    /**
     * Flush the cached entries.
     *
     * @return void
     */
    public function flush()
    {
        $this->hydrationEntries = [];
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
            '*created*', '*updated*', '*restored*', '*deleted*', '*retrieved*',
        ], $eventName);
    }

    /**
     * Determine if the hydration should be recorded for the model class.
     *
     * @param  string  $modelClass
     * @return bool
     */
    private function shouldRecordHydration($modelClass)
    {
        return collect($this->options['ignore'] ?? [EntryModel::class])
            ->every(function ($class) use ($modelClass) {
                return $modelClass !== $class && ! is_subclass_of($modelClass, $class);
            });
    }
}
