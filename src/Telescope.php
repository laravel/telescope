<?php

namespace Laravel\Telescope;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Laravel\Telescope\Watchers\{
    JobWatcher,
    LogWatcher,
    MailWatcher,
    MigrationWatcher,
    ModelWatcher,
    NotificationWatcher,
    QueryWatcher,
    RedisWatcher,
    RequestWatcher,
    ScheduleWatcher,
    StorageWatcher,
    TelescopeEntry
};

class Telescope
{
    /**
     * The name of the app.
     *
     * @var string
     */
    public static $name = 'Telescope';

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    public static $app;

    /**
     * The watchers to be registered.
     *
     * @var array
     */
    public static $watchers = [
        RequestWatcher::class,
        QueryWatcher::class,
        LogWatcher::class,
        MailWatcher::class,
        JobWatcher::class,
        ModelWatcher::class,
        NotificationWatcher::class,
        RedisWatcher::class,
        ScheduleWatcher::class,
        MigrationWatcher::class,
        StorageWatcher::class,
    ];

    /**
     * The request parameters that should be hidden from the Telescope UI.
     *
     * @var array
     */
    public static $hiddenRequestParameters = [
        'password', 'credit_card_number', 'api_key', 'password_confirmation'
    ];

    /**
     * The request headers that should be hidden from the Telescope UI.
     *
     * @var array
     */
    public static $hiddenRequestHeaders = [
        'authorization', 'php-auth-pw', 'x-api-key',
    ];

    /**
     * The tags to be added to the entries recorded by Telescope.
     *
     * @var array
     */
    public static $tagUsing = [
        function ($entry) {
            return ['user_id' => Auth::id()];
        },
    ];

    /**
     * The filters used for determining which entries should be recorded.
     *
     * @var array
     */
    public static $filterUsing = [
        function ($entry) {
            return $entry->type !== 'exception'; // Don't record exceptions
        },
    ];

    /**
     * Start recording the Telescope entries.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public static function start($app)
    {
        if (! config('telescope.enabled') || ! Auth::check()) {
            return; // Disable telescope recording if the user is not authenticated
        }

        static::registerWatchers($app);
        static::registerMailableTagExtractor();
        static::startRecording();
    }

    /**
     * Register the watchers for the application.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected static function registerWatchers($app)
    {
        foreach (static::$watchers as $watcher) {
            $app->singleton($watcher);
        }
    }

    /**
     * Start recording the Telescope entries.
     *
     * @return void
     */
    protected static function startRecording()
    {
        static::$shouldRecord = true;
    }

    /**
     * Register the mailable tag extractor.
     *
     * @return void
     */
    protected static function registerMailableTagExtractor()
    {
        //
    }

    /**
     * Stop recording the Telescope entries.
     *
     * @return void
     */
    public static function stopRecording()
    {
        static::$shouldRecord = false;
    }

    /**
     * Log a new Telescope entry.
     *
     * @param \Laravel\Telescope\TelescopeEntry $entry
     * @return void
     */
    public static function log(TelescopeEntry $entry)
    {
        if (static::$shouldRecord) {
            event(new EntryLogged($entry));
        }
    }

    /**
     * Check if the given entry should be recorded.
     *
     * @param \Laravel\Telescope\TelescopeEntry $entry
     * @return bool
     */
    protected static function shouldRecordEntry(TelescopeEntry $entry)
    {
        foreach (static::$filterUsing as $callback) {
            if ($callback($entry) === false) {
                return false;
            }
        }

        return true;
    }
}
