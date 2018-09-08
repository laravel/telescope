<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Events\CommandStarting;

class ScheduleWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CommandStarting::class, [$this, 'recordCommand']);
    }

    /**
     * Record a scheduled command was executed.
     *
     * @param  \Illuminate\Console\Events\CommandFinished  $event
     * @return void
     */
    public function recordCommand(CommandStarting $event)
    {
        if ($event->command != 'schedule:run') {
            return;
        }

        collect(app(Schedule::class)->events())->each(function ($event) {
            $event->then(function () use ($event) {
                Telescope::recordScheduledCommand(IncomingEntry::make([
                    'command' => $event->command,
                    'expression' => $event->expression,
                    'description' => $event->getSummaryForDisplay(),
                    'timezone' => $event->timezone,
                    'user' => $event->user,
                ]));
            });
        });
    }
}
