<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Events\QueryExecuted;

class QueryWatcher extends Watcher
{
    use FetchesStackTrace;

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(QueryExecuted::class, [$this, 'recordQuery']);
    }

    /**
     * Record a query was executed.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function recordQuery(QueryExecuted $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $time = $event->time;

        $caller = $this->getCallerFromStackTrace();

        Telescope::recordQuery(IncomingEntry::make([
            'connection' => $event->connectionName,
            'bindings' => $this->formatBindings($event),
            'sql' => $event->sql,
            'time' => number_format($time, 2),
            'slow' => isset($this->options['slow']) && $time >= $this->options['slow'],
            'file' => $caller['file'],
            'line' => $caller['line'],
        ])->tags($this->tags($event)));
    }

    /**
     * Get the tags for the query.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function tags($event)
    {
        return isset($this->options['slow']) && $event->time >= $this->options['slow'] ? ['slow'] : [];
    }

    /**
     * Format the given bindings to strings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function formatBindings($event)
    {
        return $event->connection->prepareBindings($event->bindings);
    }
}
