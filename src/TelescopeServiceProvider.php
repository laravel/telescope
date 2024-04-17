<?php

namespace Laravel\Telescope;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Contracts\ClearableRepository;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Contracts\PrunableRepository;
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
        $this->registerCommands();
        $this->registerPublishing();

        if (! config('telescope.enabled')) {
            return;
        }

        Route::middlewareGroup('telescope', config('telescope.middleware', []));

        $this->registerRoutes();
        $this->registerResources();

        Telescope::start($this->app);
        Telescope::listenForStorageOpportunities($this->app);
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'domain' => config('telescope.domain', null),
            'namespace' => 'Laravel\Telescope\Http\Controllers',
            'prefix' => config('telescope.path'),
            'middleware' => 'telescope',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the Telescope resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'telescope');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
                ? 'publishesMigrations'
                : 'publishes';

            $this->{$publishesMigrationsMethod}([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'telescope-migrations');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/telescope'),
            ], ['telescope-assets', 'laravel-assets']);

            $this->publishes([
                __DIR__.'/../config/telescope.php' => config_path('telescope.php'),
            ], 'telescope-config');

            $this->publishes([
                __DIR__.'/../stubs/TelescopeServiceProvider.stub' => app_path('Providers/TelescopeServiceProvider.php'),
            ], 'telescope-provider');
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ClearCommand::class,
                Console\InstallCommand::class,
                Console\PauseCommand::class,
                Console\PruneCommand::class,
                Console\PublishCommand::class,
                Console\ResumeCommand::class,
            ]);
        }
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

        $this->registerStorageDriver();
    }

    /**
     * Register the package storage driver.
     *
     * @return void
     */
    protected function registerStorageDriver()
    {
        $driver = config('telescope.driver');

        if (method_exists($this, $method = 'register'.ucfirst($driver).'Driver')) {
            $this->$method();
        }
    }

    /**
     * Register the package database storage driver.
     *
     * @return void
     */
    protected function registerDatabaseDriver()
    {
        $this->app->singleton(
            EntriesRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            ClearableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            PrunableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give(config('telescope.storage.database.connection'));

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$chunkSize')
            ->give(config('telescope.storage.database.chunk'));
    }
}
