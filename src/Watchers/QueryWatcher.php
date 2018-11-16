<?php

namespace Laravel\Telescope\Watchers;

use PDO;
use Illuminate\Support\Str;
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
            'explains' => $this->explains($event),
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
     * Find the first frame in the stack trace outside of Telescope/Laravel.
     *
     * @return array
     */
    protected function getCallerFromStackTrace()
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget(0);

        return $trace->first(function ($frame) {
            if (! isset($frame['file'])) {
                return false;
            }

            return ! Str::contains(
                $frame['file'],
                base_path('vendor'.DIRECTORY_SEPARATOR.'laravel')
            );
        });
    }

    /**
     * Run an explain query on the given event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function explains(QueryExecuted $event)
    {
        if (! $this->shouldRunExplainQuery($event)) {
            return [];
        }

        $statement = $event->connection->getPdo()->prepare("EXPLAIN {$event->sql}");

        $statement->execute($event->bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Determine if the explain query should run.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return bool
     */
    protected function shouldRunExplainQuery(QueryExecuted $event)
    {
        if (! array_get($this->options, 'explain.enabled', false)) {
            return false;
        }

        if ($event->connection->getDriverName() !== 'mysql') {
            return false;
        }

        return Str::startsWith(
            $event->sql,
            array_get($this->options, 'explain.types', [])
        );
    }
}
