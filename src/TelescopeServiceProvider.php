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
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/Storage/migrations');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'telescope');

        $this->registerRoutes();

        $this->storeEntriesBeforeTermination();

        $this->storeEntriesAfterWorkerLoop();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $watchers = [
            Watchers\LogWatcher::class,
            Watchers\MailWatcher::class,
            Watchers\QueueWatcher::class,
            Watchers\CacheWatcher::class,
            Watchers\EventsWatcher::class,
            Watchers\NotificationWatcher::class,
        ];

        foreach ($watchers as $watcher) {
            (new $watcher)->register($this->app);
        }

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
}