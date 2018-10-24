<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Redis\Events\CommandExecuted;

class RedisWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CommandExecuted::class, [$this, 'recordCommand']);
    }

    /**
     * Record a Redis command was executed.
     *
     * @param  \Illuminate\Redis\Events\CommandExecuted  $event
     * @return void
     */
    public function recordCommand(CommandExecuted $event)
    {
        Telescope::recordRedis(IncomingEntry::make([
            'connection' => $event->connectionName,
            'command' => $this->formatCommand($event->command, $event->parameters),
            'time' => number_format($event->time, 2),
        ]));
    }

    /**
     * Format the given Redis command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return string
     */
    private function formatCommand($command, $parameters)
    {
        return $command.' '.implode(' ', $parameters);
    }
}
