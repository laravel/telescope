<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Illuminate\Database\Events\QueryExecuted;

class QueriesWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(QueryExecuted::class, [$this, 'recordNewQuery']);
    }

    /**
     * Record a new query was executed.
     * 
     * @param \Illuminate\Database\Events\QueryExecuted $event
     * @return void
     */
    public function recordNewQuery(QueryExecuted $event)
    {
        Telescope::record(7, [
            'connection' => $event->connectionName,
            'bindings' => $event->bindings,
            'sql' => $event->sql,
            'time' => $event->time,
        ]);
    }
}