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
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerPublishing();

        if (! config('telescope.enabled')) {
            return;
        }

        Route::middlewareGroup('telescope', config('telescope.middleware', []));

        $this->registerRoutes();
        $this->registerMigrations();

        Telescope::start($this->app);
        Telescope::listenForStorageOpportunities($this->app);

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'telescope'
        );
    }

    /**
     * Register the package routes.
     */
    private function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        });
    }

    /**
     * Get the Telescope route group configuration array.
     */
    private function routeConfiguration(): array
    {
        return [
            'domain' => config('telescope.domain'),
            'namespace' => 'Laravel\Telescope\Http\Controllers',
            'prefix' => config('telescope.path'),
            'middleware' => 'telescope',
        ];
    }

    /**
     * Register the package's migrations.
     */
    private function registerMigrations(): void
    {
        if ($this->app->runningInConsole() && $this->shouldMigrate()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the package's publishable resources.
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
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
     */
    protected function registerCommands(): void
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

        $this->registerTerminator();
    }

    /**
     * Register the package storage driver.
     *
     * @uses \Laravel\Telescope\TelescopeServiceProvider::registerDatabaseDriver
     */
    protected function registerStorageDriver(): void
    {
        $driver = config('telescope.driver');

        if (method_exists($this, $method = 'register'.ucfirst($driver).'Driver')) {
            $this->$method();
        }
    }

    /**
     * Register the package database storage driver.
     */
    protected function registerDatabaseDriver(): void
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

    /**
     * Register a terminating callback with the application.
     */
    protected function registerTerminator(): void
    {
        if (config('telescope.enabled')) {
            register_shutdown_function(function () {
                Telescope::store(app(EntriesRepository::class));
            });
        }
    }

    /**
     * Determine if we should register the migrations.
     */
    protected function shouldMigrate(): bool
    {
        return Telescope::$runsMigrations && config('telescope.driver') === 'database';
    }
}
