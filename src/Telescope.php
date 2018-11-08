<?php

namespace Laravel\Telescope;

use Closure;
use Exception;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Contracts\TerminableRepository;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Telescope
{
    use AuthorizesRequests,
        ExtractsMailableTags,
        ListensForStorageOpportunities,
        RegistersWatchers;

    /**
     * The callbacks that filter the entries that should be recorded.
     *
     * @var array
     */
    public static $filterUsing = [];

    /**
     * The callbacks that filter the batches that should be recorded.
     *
     * @var array
     */
    public static $filterBatchUsing = [];

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
    public static $entriesQueue = [];

    /**
     * The list of queued entry updates.
     *
     * @var array
     */
    public static $updatesQueue = [];

    /**
     * The list of hidden request headers.
     *
     * @var array
     */
    public static $hiddenRequestHeaders = [
        'authorization',
    ];

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
        if ($app->runningUnitTests()) {
            return;
        }

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
            ], config('telescope.ignoreCommands', []), config('telescope.ignore_commands', []))
        );
    }

    /**
     * Determine if the application is handling a request not originating from Telescope, or Horizon.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return bool
     */
    protected static function handlingNonTelescopeRequest($app)
    {
        return ! $app->runningInConsole() && ! $app['request']->is(
            config('telescope.path').'*',
            'telescope-api*',
            'vendor/telescope*',
            'horizon*',
            'vendor/horizon*'
        );
    }

    /**
     * Start recording entries.
     *
     * @return void
     */
    public static function startRecording()
    {
        static::$shouldRecord = ! cache('telescope:pause-recording');
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
     * Execute the given callback without recording Telescope entries.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function withoutRecording($callback)
    {
        $shouldRecord = static::$shouldRecord;

        static::$shouldRecord = false;

        call_user_func($callback);

        static::$shouldRecord = $shouldRecord;
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
        if (! static::$shouldRecord) {
            return;
        }

        $entry->type($type)->tags(
            static::$tagUsing ? call_user_func(static::$tagUsing, $entry) : []
        );

        if (Auth::hasUser()) {
            $entry->user(Auth::user());
        }

        static::withoutRecording(function () use ($entry) {
            if (collect(static::$filterUsing)->every->__invoke($entry)) {
                static::$entriesQueue[] = $entry;
            }
        });
    }

    /**
     * Record the given entry update.
     *
     * @param  \Laravel\Telescope\EntryUpdate  $update
     * @return void
     */
    public static function recordUpdate(EntryUpdate $update)
    {
        if (static::$shouldRecord) {
            static::$updatesQueue[] = $update;
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
        static::record(EntryType::CACHE, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordCommand(IncomingEntry $entry)
    {
        static::record(EntryType::COMMAND, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordDump(IncomingEntry $entry)
    {
        static::record(EntryType::DUMP, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordEvent(IncomingEntry $entry)
    {
        static::record(EntryType::EVENT, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordException(IncomingEntry $entry)
    {
        static::record(EntryType::EXCEPTION, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordJob($entry)
    {
        static::record(EntryType::JOB, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordLog(IncomingEntry $entry)
    {
        static::record(EntryType::LOG, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordMail(IncomingEntry $entry)
    {
        static::record(EntryType::MAIL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordNotification($entry)
    {
        static::record(EntryType::NOTIFICATION, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordQuery(IncomingEntry $entry)
    {
        static::record(EntryType::QUERY, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordModelEvent(IncomingEntry $entry)
    {
        static::record(EntryType::MODEL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordRedis(IncomingEntry $entry)
    {
        static::record(EntryType::REDIS, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordRequest(IncomingEntry $entry)
    {
        static::record(EntryType::REQUEST, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    public static function recordScheduledCommand(IncomingEntry $entry)
    {
        static::record(EntryType::SCHEDULED_TASK, $entry);
    }

    /**
     * Flush all entries in the queue.
     *
     * @return static
     */
    public static function flushEntries()
    {
        static::$entriesQueue = [];

        return new static;
    }

    /**
     * Record the given exception.
     *
     * @param  \Throwable|\Exception  $e
     * @param  array  $tags
     * @return void
     */
    public static function catch($e, $tags = [])
    {
        if ($e instanceof Throwable && ! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        event(new MessageLogged('error', $e->getMessage(), [
            'exception' => $e,
            'telescope' => $tags,
        ]));
    }

    /**
     * Set the callback that filters the entries that should be recorded.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function filter(Closure $callback)
    {
        static::$filterUsing[] = $callback;

        return new static;
    }

    /**
     * Set the callback that filters the batches that should be recorded.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function filterBatch(Closure $callback)
    {
        static::$filterBatchUsing[] = $callback;

        return new static;
    }

    /**
     * Set the callback that adds tags to the record.
     *
     * @param  \Closure  $callback
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
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @return void
     */
    public static function store(EntriesRepository $storage)
    {
        if (empty(static::$entriesQueue) && empty(static::$updatesQueue)) {
            return;
        }

        if (! collect(static::$filterBatchUsing)->every->__invoke(collect(static::$entriesQueue))) {
            static::flushEntries();
        }

        try {
            $batchId = Str::orderedUuid()->toString();

            $storage->store(static::collectEntries($batchId));
            $storage->update(static::collectUpdates($batchId));

            if ($storage instanceof TerminableRepository) {
                $storage->terminate();
            }
        } catch (Exception $e) {
            app(ExceptionHandler::class)->report($e);
        }

        static::$entriesQueue = [];
        static::$updatesQueue = [];
    }

    /**
     * Collect the entries for storage.
     *
     * @param  string  $batchId
     * @return \Illuminate\Support\Collection
     */
    protected static function collectEntries($batchId)
    {
        return collect(static::$entriesQueue)
            ->each(function ($entry) use ($batchId) {
                $entry->batchId($batchId);

                if ($entry->isDump()) {
                    $entry->assignEntryPointFromBatch(static::$entriesQueue);
                }
            });
    }

    /**
     * Collect the updated entries for storage.
     *
     * @param  string  $batchId
     * @return \Illuminate\Support\Collection
     */
    protected static function collectUpdates($batchId)
    {
        return collect(static::$updatesQueue)
            ->each(function ($entry) use ($batchId) {
                $entry->change(['updated_batch_id' => $batchId]);
            });
    }

    /**
     * Hide the given request header.
     *
     * @param  array  $headers
     * @return static
     */
    public static function hideRequestHeaders(array $headers)
    {
        static::$hiddenRequestHeaders = array_merge(
            static::$hiddenRequestHeaders, $headers
        );

        return new static;
    }

    /**
     * Hide the given request parameters.
     *
     * @param  array  $attributes
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
     * Specifies that Telescope should record events fired by Laravel.
     *
     * @return static
     */
    public static function recordFrameworkEvents()
    {
        static::$ignoreFrameworkEvents = false;

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

    /**
     * Get the default JavaScript variables for Telescope.
     *
     * @return array
     */
    public static function scriptVariables()
    {
        return [
            'path' => config('telescope.path'),
            'timezone' => config('app.timezone'),
            'recording' => ! cache('telescope:pause-recording')
        ];
    }
}
