<?php

namespace Laravel\Telescope;

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
     * @param  \Illuminate\Foundation\Application  $app
     * @param  bool  $bootOnly
     * @return void
     */
    protected static function registerWatchers($app, $bootOnly = false)
    {
        if (! config('telescope.enabled')) {
            return;
        }

        foreach (config('telescope.watchers') as $key => $watcher) {
            if (is_string($key) && $watcher === false) {
                continue;
            }

            if (is_array($watcher) && ! ($watcher['enabled'] ?? true)) {
                continue;
            }

            $watcher = $app->make(is_string($key) ? $key : $watcher, [
                'options' => is_array($watcher) ? $watcher : [],
            ]);

            static::$watchers[] = get_class($watcher);

            if (method_exists($watcher, 'boot')) {
                $watcher->boot($app);
            }

            if (! $bootOnly) {
                $watcher->register($app);
            }
        }
    }
}
