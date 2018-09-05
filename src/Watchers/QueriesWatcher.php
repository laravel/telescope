<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Entry;
use Laravel\Telescope\Telescope;
use Illuminate\Database\Events\QueryExecuted;

class QueriesWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(QueryExecuted::class, [$this, 'recordQuery']);
    }

    /**
     * Record a new query was executed.
     *
     * @param \Illuminate\Database\Events\QueryExecuted $event
     * @return void
     */
    public function recordQuery(QueryExecuted $event)
    {
        Telescope::recordQuery(Entry::make([
            'connection' => $event->connectionName,
            'bindings' => $event->bindings,
            'sql' => $event->sql,
            'time' => $event->time,
        ]));
    }
}
