<?php

namespace Laravel\Telescope\Watchers;

use ReflectionClass;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Events\QueryExecuted;

class QueryWatcher extends Watcher
{
    /**
     * The views that have been located by the view finder.
     *
     * @var \ReflectionProperty
     */
    protected $views;

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
        $source = $this->getSource();

        Telescope::recordQuery(IncomingEntry::make([
            'connection' => $event->connectionName,
            'bindings' => $this->formatBindings($event),
            'sql' => $event->sql,
            'time' => number_format($time, 2),
            'slow' => isset($this->options['slow']) && $time >= $this->options['slow'],
            'source' => $source,
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
     * Get the query source from the backtrace.
     *
     * @return array
     */
    protected function getSource()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50);

        return array_values(array_filter(array_map([$this, 'parseTrace'], $trace)));
    }

    /**
     * Parse the backtrace.
     *
     * @param  array  $trace
     * @return array|null
     */
    protected function parseTrace(array $trace)
    {
        if (! isset($trace['class']) || ! isset($trace['file']) || $this->fileIsInExcludedPath($trace['file'])) {
            return null;
        }

        if (strpos($trace['file'], storage_path()) !== false) {
            $hash = pathinfo($trace['file'], PATHINFO_FILENAME);
            $file = $this->findViewFromHash($hash) ?: $hash;
        } else {
            $file = file_exists($trace['file']) ? realpath($trace['file']) : $trace['file'];
        }

        return [
            'file' => $file,
            'line' => $trace['line'] ?? '?',
        ];
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        $excludedPaths = [
            '/vendor/laravel/framework/src/Illuminate/Database',
            '/vendor/laravel/framework/src/Illuminate/Events',
            '/vendor/laravel/telescope',
        ];

        $normalizedPath = str_replace('\\', '/', $file);
        foreach ($excludedPaths as $excludedPath) {
            if (strpos($normalizedPath, $excludedPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the view template name from the hash.
     *
     * @param  string $hash
     * @return null|string
     */
    protected function findViewFromHash($hash)
    {
        $finder = app('view')->getFinder();

        if (! isset($this->views)) {
            $this->views = (new ReflectionClass($finder))->getProperty('views');
            $this->views->setAccessible(true);
        }

        foreach ($this->views->getValue($finder) as $name => $path) {
            if (sha1($path) === $hash || md5($path) === $hash) {
                return $name;
            }
        }

        return null;
    }
}
