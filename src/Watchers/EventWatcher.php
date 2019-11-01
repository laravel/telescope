<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Laravel\Telescope\ExtractProperties;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use ReflectionFunction;

class EventWatcher extends Watcher
{
    use FormatsClosure;

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen('*', [$this, 'recordEvent']);
    }

    /**
     * Record an event was fired.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @return void
     */
    public function recordEvent($eventName, $payload)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($eventName)) {
            return;
        }

        $formattedPayload = $this->extractPayload($eventName, $payload);

        Telescope::recordEvent(IncomingEntry::make([
            'name' => $eventName,
            'payload' => empty($formattedPayload) ? null : $formattedPayload,
            'listeners' => $this->formatListeners($eventName),
            'broadcast' => class_exists($eventName)
                        ? in_array(ShouldBroadcast::class, (array) class_implements($eventName))
                        : false,
        ])->tags(class_exists($eventName) && isset($payload[0]) ? ExtractTags::from($payload[0]) : []));
    }

    /**
     * Extract the payload and tags from the event.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @return array
     */
    protected function extractPayload($eventName, $payload)
    {
        if (class_exists($eventName) && isset($payload[0]) && is_object($payload[0])) {
            return ExtractProperties::from($payload[0]);
        }

        return collect($payload)->map(function ($value) {
            return is_object($value) ? [
                'class' => get_class($value),
                'properties' => json_decode(json_encode($value), true),
            ] : $value;
        })->toArray();
    }

    /**
     * Format list of event listeners.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function formatListeners($eventName)
    {
        return collect(app('events')->getListeners($eventName))
            ->map(function ($listener) {
                $listener = (new ReflectionFunction($listener))
                        ->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return Str::contains($listener, '@') ? $listener : $listener.'@handle';
                } elseif (is_array($listener)) {
                    return get_class($listener[0]).'@'.$listener[1];
                }

                return $this->formatClosureListener($listener);
            })->reject(function ($listener) {
                return Str::contains($listener, 'Laravel\\Telescope');
            })->map(function ($listener) {
                if (Str::contains($listener, '@')) {
                    $queued = in_array(ShouldQueue::class, class_implements(explode('@', $listener)[0]));
                }

                return [
                    'name' => $listener,
                    'queued' => $queued ?? false,
                ];
            })->values()->toArray();
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function shouldIgnore($eventName)
    {
        return $this->eventIsIgnored($eventName) ||
            (Telescope::$ignoreFrameworkEvents && $this->eventIsFiredByTheFramework($eventName));
    }

    /**
     * Determine if the event was fired internally by Laravel.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function eventIsFiredByTheFramework($eventName)
    {
        return Str::is(
            ['Illuminate\*', 'eloquent*', 'bootstrapped*', 'bootstrapping*', 'creating*', 'composing*'],
            $eventName
        );
    }

    /**
     * Determine if the event is ignored manually.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function eventIsIgnored($eventName)
    {
        return Str::is($this->options['ignore'] ?? [], $eventName);
    }
}
