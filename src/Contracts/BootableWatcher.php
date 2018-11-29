<?php

namespace Laravel\Telescope\Contracts;

interface BootableWatcher
{
    /**
     * Boot the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function boot($app);
}
