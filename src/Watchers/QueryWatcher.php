<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Events\QueryExecuted;

class QueryWatcher extends Watcher
{
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

    /**
     * Find the first frame in the stack trace outside of Telescope/Laravel
     *
     * @return array
     */
    protected function getCallerFromStackTrace()
    {
        // Get stack trace without objects or args
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        // Remove the first frame from the trace (this method)
        $trace->forget(0);

        // Get the first frame that has a file path outside of {base_path}/vendor/laravel
        $frameworkPath = base_path('vendor'.DIRECTORY_SEPARATOR.'laravel');
        return $trace->first(function ($frame) use ($frameworkPath) {
            return strpos(array_get($frame, 'file'), $frameworkPath) !== 0;
        }, ['file' => 'Unknown', 'line' => 0]);
    }
}
