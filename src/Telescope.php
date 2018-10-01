<?php

namespace Laravel\Telescope;

use Closure;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Contracts\TerminableRepository;

class Telescope
{
    use ExtractsMailableTags,
        ListensForStorageOpportunities,
        RegistersWatchers;

    /**
     * The callbacks that filter the entries that should be recorded.
     *
     * @var array
     */
    public static $filterUsing = [];

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
     * The list of hidden request parameters.
     *
     * @var array
     */
    public static $hiddenRequestParameters = [
        'password',
        'password_confirmation',
    ];

    /**
     * Indicates if Telescope should ignore events fired by Laravel.
     *
     * @var bool
     */
    public static $ignoreFrameworkEvents = true;

    /**
     * Indicates if Telescope should use the dark theme.
     *
     * @var bool
     */
    public static $useDarkTheme = false;

    /**
     * Indicates if Telescope should record entries.
     *
     * @var bool
     */
    public static $shouldRecord = false;

    /**
     * Register the Telescope watchers and start recording if necessary.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public static function start($app)
    {
        static::registerWatchers($app);

        static::registerMailableTagExtractor();

        if (static::runningApprovedArtisanCommand($app) ||
            static::handlingNonTelescopeRequest($app)) {
            static::startRecording();
        }
    }

    /**
     * Determine if the application is running an approved command.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return bool
     */
    protected static function runningApprovedArtisanCommand($app)
    {
        return $app->runningInConsole() && ! in_array(
            $_SERVER['argv'][1] ?? null,
            array_merge([
                // 'migrate',
                'migrate:rollback',
                'migrate:fresh',
                // 'migrate:refresh',
                'migrate:reset',
                'migrate:install',
                'queue:listen',
                'queue:work',
                'horizon',
                'horizon:work',
                'horizon:supervisor',
            ], config('telescope.ignoreCommands', []))
        );
    }

    /**
     * Determine if the application is handling a request not originating from Telescope.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return bool
     */
    protected static function handlingNonTelescopeRequest($app)
    {
        return ! $app->runningInConsole() && ! $app['request']->is(
            'telescope*',
            'telescope-api*',
            'vendors/telescope*'
        );
    }

    /**
     * Start recording entries.
     *
     * @return void
     */
    public static function startRecording()
    {
        static::$shouldRecord = true;
    }

    /**
     * Stop recording entries.
     *
     * @return void
     */
    public static function stopRecording()
    {
        static::$shouldRecord = false;
    }

    /**
     * Record the given entry.
     *
     * @param  string  $type
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    protected static function record(string $type, IncomingEntry $entry)
    {
        if (static::$shouldRecord) {
            static::$entriesQueue[] = $entry->type($type);
        }
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordCache(IncomingEntry $entry)
    {
        return static::record(EntryType::CACHE, $entry);
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
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordDump(IncomingEntry $entry)
    {
        return static::record(EntryType::DUMP, $entry);
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
    public static function recordException(IncomingEntry $entry)
    {
        return static::record(EntryType::EXCEPTION, $entry);
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
    public static function recordLog(IncomingEntry $entry)
    {
        return static::record(EntryType::LOG, $entry);
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
    public static function recordModelEvent(IncomingEntry $entry)
    {
        return static::record(EntryType::MODEL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordRedis(IncomingEntry $entry)
    {
        return static::record(EntryType::REDIS, $entry);
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

    public static function recordScheduledCommand(IncomingEntry $entry)
    {
        return static::record(EntryType::SCHEDULED_COMMAND, $entry);
    }

    /**
     * Set the callback that filters the entries that should be recorded.
     *
     * @param  \Closure $callback
     * @return static
     */
    public static function filter(Closure $callback)
    {
        static::$filterUsing[] = $callback;

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
        if (empty(static::$entriesQueue)) {
            return;
        }

        try {
            $storage->store(static::collectEntries(Str::orderedUuid()));

            if ($storage instanceof TerminableRepository) {
                $storage->terminate();
            }
        } catch (Exception $e) {
            resolve(ExceptionHandler::class)->report($e);
        }

        static::$entriesQueue = [];
    }

    /**
     * Collection the entries for storage.
     *
     * @param  string  $batchId
     * @return \Illuminate\Support\Collection
     */
    protected static function collectEntries($batchId)
    {
        return collect(static::$entriesQueue)
            ->each(function ($entry) use ($batchId) {
                $entry->batchId($batchId);

                if (auth()->user()) {
                    $entry->user(auth()->user());
                }

                if ($tagger = static::$tagUsing) {
                    $entry->tags($tagger($entry));
                }

                if ($entry->isDump()) {
                    $entry->assignEntryPointFromBatch(static::$entriesQueue);
                }
            })->when(! empty(static::$filterUsing), function ($entries) {
                return static::applyFilters($entries);
            });
    }

    /**
     * Apply the filter callbacks to the given collection.
     *
     * @param  \Illuminate\Support\Collection  $entries
     * @return \Illuminate\Support\Collection
     */
    protected static function applyFilters(Collection $entries)
    {
        foreach (static::$filterUsing as $filter) {
            $entries = $entries->filter($filter);
        }

        return $entries;
    }

    /**
     * Hide the given request parameters;
     *
     * @param  $attributes  array
     * @return static
     */
    public static function hideRequestParameters(array $attributes)
    {
        static::$hiddenRequestParameters = array_merge(
            static::$hiddenRequestParameters, $attributes
        );

        return new static;
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

    /**
     * Specifies that Telescope should use the dark theme.
     *
     * @return static
     */
    public static function night()
    {
        static::$useDarkTheme = true;

        return new static;
    }
}
