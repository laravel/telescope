<?php

namespace Laravel\Telescope;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();

        $this->registerMigrations();

        $this->registerPublishing();

        $this->storeEntriesBeforeTermination();

        $this->storeEntriesAfterWorkerLoop();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'telescope');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/telescope.php', 'telescope'
        );

        $this->registerWatchers();

        $this->app->singleton(EntriesRepository::class, DatabaseEntriesRepository::class);
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        });
    }

    /**
     * Get the Nova route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'namespace' => 'Laravel\Telescope\Http\Controllers',
            'prefix' => 'telescope',
        ];
    }

    /**
     * Store the entries in queue before the application termination.
     *
     * @return void
     */
    private function storeEntriesBeforeTermination()
    {
        $this->app->terminating(function () {
            if (isset($this->app['request']) && $this->requestIsFromTelescope($this->app['request'])) {
                return;
            }

            Telescope::store($this->app[EntriesRepository::class]);
        });
    }

    /**
     * Determine if the request is coming from Telescope itself.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    private function requestIsFromTelescope(Request $request)
    {
        return $request->is('vendors/telescope*', 'telescope*', 'telescope-api*');
    }

    /**
     * Store entries after the queue worker loops.
     *
     * @return void
     */
    private function storeEntriesAfterWorkerLoop()
    {
        $this->app['events']->listen(JobProcessed::class, function ($event) {
            Telescope::store($this->app[EntriesRepository::class]);
        });

        $this->app['events']->listen(JobFailed::class, function ($event) {
            Telescope::store($this->app[EntriesRepository::class]);
        });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/telescope.php' => config_path('telescope.php'),
            ], 'telescope-config');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendors/telescope'),
            ], 'telescope-assets');
        }
    }

    /**
     * Register the package's migrations.
     *
     * @return void
     */
    private function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/Storage/migrations');
        }
    }

    /**
     * Register Telescope watchers.
     *
     * @return void
     */
    private function registerWatchers()
    {
        $watchers = [
            Watchers\ExceptionWatcher::class => config('telescope.watchers.exceptions.enabled'),
            Watchers\LogWatcher::class => config('telescope.watchers.logs.enabled'),
            Watchers\MailWatcher::class => config('telescope.watchers.mail.enabled'),
            Watchers\QueueWatcher::class => config('telescope.watchers.queue.enabled'),
            Watchers\CacheWatcher::class => config('telescope.watchers.cache.enabled'),
            Watchers\EventsWatcher::class => config('telescope.watchers.events.enabled'),
            Watchers\NotificationsWatcher::class => config('telescope.watchers.notifications.enabled'),
            Watchers\QueriesWatcher::class => config('telescope.watchers.queries.enabled'),
            Watchers\RequestsWatcher::class => config('telescope.watchers.requests.enabled'),
            Watchers\ArtisanWatcher::class => config('telescope.watchers.artisan.enabled'),
        ];

        foreach (array_keys(array_filter($watchers)) as $watcher) {
            (new $watcher)->register($this->app);
        }
    }
}
