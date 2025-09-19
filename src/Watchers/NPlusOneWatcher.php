<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class NPlusOneWatcher extends Watcher
{
    /**
     * Number of repeats before flagging N+1.
     *
     * @var int
     */
    protected $threshold = 3;

    /**
     * Aggregation window in milliseconds.
     *
     * @var int
     */
    protected $windowMs = 1000;

    /**
     * Key (pattern|caller) => aggregate.
     *
     * @var array<string, array{count:int, first:int, example:string, pattern:string, caller:string}>
     */
    protected static $batches = [];

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
     * Handle a database query event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function recordQuery(QueryExecuted $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $sql = $event->sql;

        // Avoid recursion: ignore Telescope’s own tables.
        if ($this->isTelescopeSql($sql)) {
            return;
        }

        $now = (int) (microtime(true) * 1000);

        $pattern = $this->normalize($sql);
        $caller = $this->caller();
        $key = sha1($pattern.'|'.$caller);

        if (! isset(self::$batches[$key]) || $now - self::$batches[$key]['first'] > $this->windowMs) {
            self::$batches[$key] = [
                'count' => 0,
                'first' => $now,
                'example' => $sql,
                'pattern' => $pattern,
                'caller' => $caller,
            ];
        }

        self::$batches[$key]['count']++;

        if (self::$batches[$key]['count'] === $this->threshold) {
            Telescope::recordLog(IncomingEntry::make([
                'level' => 'warning',
                'message' => 'N+1 detected at '.$caller,
                'context' => [
                    'pattern' => $pattern,
                    'example' => self::$batches[$key]['example'],
                    'threshold' => $this->threshold,
                    'window_ms' => $this->windowMs,
                ],
            ]));
        }
    }

    /**
     * Normalize SQL heuristically.
     *
     * @param  string  $sql
     * @return string
     */
    protected function normalize($sql)
    {
        $s = strtolower($sql);
        $s = preg_replace('/\s+/', ' ', $s) ?: $s;
        $s = preg_replace('/\b\d+\b/', '?', $s) ?: $s;
        $s = preg_replace('/\'(?:[^\'\\\\]|\\\\.)*\'/', '?', $s) ?: $s;
        $s = preg_replace('/\bin\s*\((?:\s*[^)]+)\)/', ' in (?)', $s) ?: $s;

        return trim($s);
    }

    /**
     * First non-vendor stack frame as "file:line".
     *
     * @return string
     */
    protected function caller()
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (! isset($frame['file'])) {
                continue;
            }
            if (Str::contains($frame['file'], DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            $line = isset($frame['line']) ? (int) $frame['line'] : 0;

            return $frame['file'].':'.$line;
        }

        return 'unknown:0';
    }

    /**
     * Check if query touches Telescope tables.
     *
     * @param  string  $sql
     * @return bool
     */
    protected function isTelescopeSql($sql)
    {
        $s = strtolower($sql);

        return Str::contains($s, 'telescope_entries')
            || Str::contains($s, 'telescope_entries_tags')
            || Str::contains($s, 'telescope_monitoring');
    }
}
