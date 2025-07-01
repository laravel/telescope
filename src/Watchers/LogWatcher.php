<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Psr\Log\LogLevel;
use Throwable;

class LogWatcher extends Watcher
{
    /**
     * The available log level priorities.
     */
    private const PRIORITIES = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordLog']);
    }

    /**
     * Record a message was logged.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return void
     */
    public function recordLog(MessageLogged $event)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        // Extract context for interpolation, and drop the 'telescope' entry
        $context = Arr::except($event->context, ['telescope']);

        // Interpolate the message with context values
        $interpolatedMessage = $this->interpolate((string) $event->message, $context);

        $context['_original_message'] = (string) $event->message;

        Telescope::recordLog(
            IncomingEntry::make([
                'level' => $event->level,
                'message' => $interpolatedMessage,
                'context' => $context,
            ])->tags($this->tags($event))
        );
    }

    /**
     * Interpolate a message with context values.
     *
     * @param  string  $message
     * @param  array   $context
     * @return string
     */
    private function interpolate(string $message, array $context): string
    {
        // Build a replacement array with keys formatted as {key}
        $replace = [];
        foreach ($context as $key => $val) {
            // Ensure that the value can be cast to string
            if (is_scalar($val) || ( is_object($val) && method_exists($val, '__toString'))) {
                $replace['{'.$key.'}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Extract tags from the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return array
     */
    private function tags($event)
    {
        return $event->context['telescope'] ?? [];
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        if (isset($event->context['exception']) && $event->context['exception'] instanceof Throwable) {
            return true;
        }

        $minimumTelescopeLogLevel = static::PRIORITIES[$this->options['level'] ?? 'debug']
                ?? static::PRIORITIES[LogLevel::DEBUG];

        $eventLogLevel = static::PRIORITIES[$event->level]
                ?? static::PRIORITIES[LogLevel::DEBUG];

        return $eventLogLevel < $minimumTelescopeLogLevel;
    }
}
