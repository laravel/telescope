<?php

namespace Laravel\Telescope\Watchers;

abstract class Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    abstract public function register($app);
}
