<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;

class CommandWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register(Application $app)
    {
        $app->make(Dispatcher::class)->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    /**
     * Record an Artisan command was executed.
     *
     * @param  \Illuminate\Console\Events\CommandFinished  $event
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
        return in_array($event->command, array_merge($this->options['ignore'] ?? [], [
            'schedule:run',
            'schedule:finish',
        ]));
    }
}
