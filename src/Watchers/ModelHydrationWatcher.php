<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;

class ModelHydrationWatcher extends Watcher
{
    /**
     * Telescope entries to store the count of models retrieved.
     *
     * @var array
     */
    public $modelEntries = [];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen('eloquent.retrieved*', [$this, 'recordAction']);

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
        if (! Telescope::isRecording() || ! $this->shouldRecord($modelClass = get_class($data[0]))) {
            return;
        }

        if (! isset($this->modelEntries[$modelClass])) {
            $this->modelEntries[$modelClass] = IncomingEntry::make([
                'model' => $modelClass,
                'count' => 1,
            ])->tags([$modelClass]);

            Telescope::recordHydrationEvent($this->modelEntries[$modelClass]);
        } else {
            $this->modelEntries[$modelClass]->content['count']++;
        }
    }

    /**
     * Flush the cached entries.
     *
     * @return void
     */
    public function flush()
    {
        $this->modelEntries = [];
    }

    /**
     * Determine if the hydration should be recorded for the model class.
     *
     * @param  string  $modelClass
     * @return bool
     */
    private function shouldRecord($modelClass)
    {
        return collect($this->options['ignore'] ?? [EntryModel::class])
            ->every(function ($class) use ($modelClass) {
                return $modelClass !== $class && ! is_subclass_of($modelClass, $class);
            });
    }
}
