<?php

namespace Laravel\Telescope;

use Closure;
use Illuminate\Support\Str;
use Laravel\Telescope\Contracts\EntriesRepository;

class Telescope
{
    /**
     * The callback that filters the entries that should be recorded.
     *
     * @var \Closure
     */
    public static $filterUsing;

    /**
     * The callback that adds tags to the record.
     *
     * @var \Closure
     */
    public static $tagUsing;

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
     * @param  int  $type
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    protected static function record($type, IncomingEntry $entry)
    {
        static::$entriesQueue[] = $entry->type($type);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordMail(IncomingEntry $entry)
    {
        return static::record(EntryType::MAIL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordLogEntry(IncomingEntry $entry)
    {
        return static::record(EntryType::LOG, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordNotification($entry)
    {
        return static::record(EntryType::NOTIFICATION, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordJob($entry)
    {
        return static::record(EntryType::JOB, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordEvent(IncomingEntry $entry)
    {
        return static::record(EntryType::EVENT, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordCacheEntry(IncomingEntry $entry)
    {
        return static::record(EntryType::CACHE, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordQuery(IncomingEntry $entry)
    {
        return static::record(EntryType::QUERY, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */

    public static function recordRequest(IncomingEntry $entry)
    {
        return static::record(EntryType::REQUEST, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */

    public static function recordCommand(IncomingEntry $entry)
    {
        return static::record(EntryType::COMMAND, $entry);
    }

    /**
     * Set the callback that filters the entries that should be recorded.
     *
     * @param  \Closure $callback
     * @return static
     */
    public static function filter(Closure $callback)
    {
        static::$filterUsing = $callback;

        return new static;
    }

    /**
     * Set the callback that adds tags to the record.
     *
     * @param  \Closure $callback
     * @return static
     */
    public static function tag(Closure $callback)
    {
        static::$tagUsing = $callback;

        return new static;
    }

    /**
     * Store the queued entries and flush the queue.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @return void
     */
    public static function store(EntriesRepository $storage)
    {
        $batchId = Str::uuid();

        $entries = collect(static::$entriesQueue);

        if (static::$filterUsing) {
            $entries = $entries->filter(static::$filterUsing);
        }

        if ($tagger = static::$tagUsing) {
            $entries = $entries->each(function ($entry) use ($tagger) {
                return $entry->tags($tagger($entry));
            });
        }

        $storage->store($entries->each(function ($entry) use ($batchId) {
            if (auth()->user()) {
                $entry->user(auth()->user());
            }

            $entry->batchId($batchId);
        }));

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
     * @return static
     */
    public static function ignoreFrameworkEvents()
    {
        static::$ignoreFrameworkEvents = true;

        return new static;
    }
}
