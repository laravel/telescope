<?php

namespace Laravel\Telescope;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\RedisEntriesRepository;

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

        Telescope::listenForStorageOpportunities($this->app);

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'telescope'
        );
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

        Telescope::start($this->app);
    }


    /**
     * Register the package storage driver.
     *
     * @return void
     */
    private function registerStorageDriver()
    {
        $driver = config('telescope.driver');

        if (method_exists($this, $method = 'register'.ucfirst($driver).'Driver')){
            $this->$method();
        }
    }

    /**
     * Register the package database storage driver.
     *
     * @return void
     */
    public function registerDatabaseDriver()
    {
        $this->app->singleton(
            EntriesRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give(config('telescope.storage.database.connection'));
    }

    /**
     * Register the package redis storage driver.
     *
     * @return void
     */
    public function registerRedisDriver()
    {
        $this->app->singleton(
            EntriesRepository::class, RedisEntriesRepository::class
        );

        $this->app->when(RedisEntriesRepository::class)
            ->needs('$connection')
            ->give(config('telescope.storage.redis.connection'));
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
     * Get the Telescope route group configuration array.
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
}
