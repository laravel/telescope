<?php

namespace Laravel\Telescope;

use Laravel\Telescope\Watchers\Watcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

trait RegistersWatchers
{
    /**
     * The class names of the registered watchers.
     *
     * @var array
     */
    protected static $watchers = [];

    /**
     * Determine if a given watcher has been registered.
     *
     * @param  string  $class
     * @return bool
     */
    public static function hasWatcher($class)
    {
        return in_array($class, static::$watchers);
    }

    /**
     * Register the configured Telescope watchers.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected static function registerWatchers(Application $app)
    {
        foreach ($app->make(Repository::class)->get('telescope.watchers') as $key => $watcher) {
            if (is_string($key) && $watcher === false) {
                continue;
            }

            if (is_array($watcher) && ! ($watcher['enabled'] ?? true)) {
                continue;
            }

            /** @var \Laravel\Telescope\Watchers\Watcher $watcher */
            $watcher = $app->make(is_string($key) ? $key : $watcher, [
                'options' => is_array($watcher) ? $watcher : []
            ]);

            static::$watchers[] = get_class($watcher);

            $watcher->register($app);
        }
    }
}
