<?php

namespace Laravel\Telescope\Watchers;

abstract class Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    abstract public function register($app);
}
