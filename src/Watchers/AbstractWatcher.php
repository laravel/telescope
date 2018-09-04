<?php

namespace Laravel\Telescope\Watchers;

abstract class AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    abstract public function register($app);
}
