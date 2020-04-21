<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Redis\Events\CommandExecuted;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

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

        foreach ((array) $app['redis']->connections() as $connection) {
            $connection->setEventDispatcher($app['events']);
        }

        $app['redis']->enableEvents();
    }

    /**
     * Record a Redis command was executed.
     *
     * @param  \Illuminate\Redis\Events\CommandExecuted  $event
     * @return void
     */
    public function recordCommand(CommandExecuted $event)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordRedis(IncomingEntry::make([
            'connection' => $event->connectionName,
            'command' => $this->formatCommand($event->command, $event->parameters),
            'time' => number_format($event->time, 2, '.', ''),
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
        $parameters = collect($parameters)->map(function ($parameter) {
            if (is_array($parameter)) {
                return collect($parameter)->map(function ($value, $key) {
                    if (is_array($value)) {
                        return json_encode($value);
                    }

                    return is_int($key) ? $value : "{$key} {$value}";
                })->implode(' ');
            }

            return $parameter;
        })->implode(' ');

        return "{$command} {$parameters}";
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
            'pipeline', 'transaction',
        ]);
    }
}
