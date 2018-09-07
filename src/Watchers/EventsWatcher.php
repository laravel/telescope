<?php

namespace Laravel\Telescope\Watchers;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Eloquent\Model;

class EventsWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen('*', [$this, 'recordEvent']);
    }

    /**
     * Record an event was fired.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return void
     */
    public function recordEvent($eventName, $payload)
    {
        if ($this->shouldIgnore($eventName)) {
            return;
        }

        [$payload, $tags] = $this->extractPayloadAndTags($eventName, $payload);

        Telescope::recordEvent(IncomingEntry::make([
            'name' => $eventName,
            'payload' => $payload,
            'listeners' => $this->formatListeners($eventName),
        ])->tags($tags));
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  string  $eventName
     * @return bool
     */
    private function shouldIgnore($eventName)
    {
        return Telescope::ignoresFrameworkEvents() &&
               $this->eventIsFiredByTheFramework($eventName);
    }

    /**
     * Determine if the event was fired internally by Laravel.
     *
     * @param  string  $eventName
     * @return bool
     */
    private function eventIsFiredByTheFramework($eventName)
    {
        return Str::is(
            ['Illuminate\*', 'eloquent*', 'bootstrapped*', 'bootstrapping*', 'creating*', 'composing*'],
            $eventName
        );
    }

    /**
     * Extract the payload and tags from the event.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @return array
     */
    private function extractPayloadAndTags($eventName, $payload)
    {
        return class_exists($eventName)
                ? $this->extractPayloadAndTagsFromEventObject($payload[0])
                : [$this->formatRawPayload($payload), []];
    }

    /**
     * Extract the payload and tags from the event object.
     *
     * @param  object $payload
     * @return array
     */
    private function extractPayloadAndTagsFromEventObject($event)
    {
        $tags = [];

        $payload = collect((new ReflectionClass($event))->getProperties())
            ->mapWithKeys(function ($property) use ($event, &$tags) {
                $property->setAccessible(true);

                if (($value = $property->getValue($event)) instanceof Model) {
                    $tags[] = $tag = get_class($value).':'.$value->getKey();

                    return [$property->getName() => $tag];
                } else {
                    return [$property->getName() => json_decode(json_encode($value), true)];
                }
            })->toArray();

        return [$payload, $tags];
    }

    /**
     * Format raw event payload.
     *
     * @param  array $payload
     * @return array
     */
    private function formatRawPayload($payload)
    {
        return collect($payload)->map(function ($value) {
            return ! is_object($value) ? $value : [
                'class' => get_class($value),
                'properties' => json_decode(json_encode($value), true),
            ];
        })->toArray();
    }

    /**
     * Format list of event listeners.
     *
     * @param  string $eventName
     * @return array
     */
    private function formatListeners($eventName)
    {
        return collect(app('events')->getListeners($eventName))
            ->map(function ($listener) {
                $listener = (new ReflectionFunction($listener))->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return (str_contains($listener, '@') ? $listener : $listener.'@handle');
                } elseif (is_array($listener)) {
                    return get_class($listener[0]).'@'.$listener[1];
                } else {
                    return $this->formatClosureListener($listener);
                }
            })
            ->reject(function ($listener) {
                return Str::contains($listener, 'Laravel\\Telescope');
            })
            ->values()
            ->toArray();
    }

    /**
     * Format a closure-based listener.
     *
     * @param Closure $listener
     * @return string
     */
    private function formatClosureListener(Closure $listener)
    {
        $listener = new ReflectionFunction($listener);

        return sprintf('Closure at %s[%s:%s]',
            $listener->getFileName(), $listener->getStartLine(), $listener->getEndLine()
        );
    }
}
