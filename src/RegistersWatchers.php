<?php

namespace Laravel\Telescope;

trait RegistersWatchers
{
    /**
     * Register the configured Telescope watchers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected static function registerWatchers($app)
    {
        foreach (config('telescope.watchers') as $key => $watcher) {
            if (is_string($key) && $watcher === false) {
                continue;
            }

            $watcher = is_string($key) ? $key : $watcher;

            (new $watcher)->register($app);
        }
    }
}
