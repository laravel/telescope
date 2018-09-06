<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Foundation\Console\Kernel;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Illuminate\Console\Events\CommandFinished;

class ArtisanWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app  \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    /**
     * Record a new query was executed.
     *
     * @param \Illuminate\Console\Events\CommandFinished  $event
     * @return void
     */
    public function recordCommand(CommandFinished $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCommand(IncomingEntry::make([
            'command' => $event->command,
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
            'output' => app(Kernel::class)->output(),
        ]));
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return in_array($event->command, [
            'queue:listen',
            'queue:work',
            'horizon',
            'horizon:work',
            'horizon:supervisor',
        ]);
    }
}
