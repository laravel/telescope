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

            if (is_array($watcher) && ! ($watcher['enabled'] ?? true)) {
                continue;
            }

            $app->makeWith(
                is_string($key) ? $key : $watcher, [
                    'options' => is_array($watcher) ? $watcher : []
                ]
            )->register($app);
        }
    }
}
