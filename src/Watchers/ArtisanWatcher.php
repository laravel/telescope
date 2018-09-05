<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Illuminate\Console\Events\CommandFinished;

class ArtisanWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    /**
     * Record a new query was executed.
     *
     * @param \Illuminate\Console\Events\CommandFinished $event
     * @return void
     */
    public function recordCommand(CommandFinished $event)
    {
        Telescope::recordCommand([
            'command' => $event->command,
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
        ]);
    }
}
