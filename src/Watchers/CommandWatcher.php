<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Context;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class CommandWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    /**
     * Record an Artisan command was executed.
     *
     * @param  \Illuminate\Console\Events\CommandFinished  $event
     * @return void
     */
    public function recordCommand(CommandFinished $event)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCommand(IncomingEntry::make([
            'command' => $event->command ?? $event->input->getArguments()['command'] ?? 'default',
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
            'context' => $this->context(),
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
            'package:discover',
        ]));
    }

    /**
     * Get the current context data.
     *
     * @return array|null
     */
    protected function context()
    {
        if (! class_exists(Context::class)) {
            return null;
        }

        $context = Context::all();

        return ! empty($context) ? $context : null;
    }
}
