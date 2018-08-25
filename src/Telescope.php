<?php

namespace Laravel\Telescope;

use Laravel\Telescope\Contracts\EntriesRepository;

class Telescope
{
    /**
     * The list of queued entries to be stored.
     *
     * @var array
     */
    public static $entriesQueue;

    /**
     * Indicates if Telescope should ignore events fired by Laravel.
     *
     * @var bool
     */
    public static $ignoreFrameworkEvents = false;

    /**
     * Record the given entry.
     *
     * @param  int $type
     * @param  array $entry
     */
    public static function record($type, $entry)
    {
        static::$entriesQueue[] = [
            'type' => $type,
            'content' => $entry,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Store the queued entries and flush the queue.
     *
     * @param  Contracts\EntriesRepository $storage
     * @param  array $entry
     */
    public static function store(EntriesRepository $storage)
    {
        $storage->store(static::$entriesQueue);

        static::$entriesQueue = [];
    }

    /**
     * Determines if Telescope is ignoring events fired by Laravel.
     *
     * @return bool
     */
    public static function ignoresFrameworkEvents()
    {
        return static::$ignoreFrameworkEvents;
    }

    /**
     * Specifies that Telescope should ignore events fired by Laravel.
     *
     * @return void
     */
    public static function ignoreFrameworkEvents()
    {
        static::$ignoreFrameworkEvents = true;
    }
}