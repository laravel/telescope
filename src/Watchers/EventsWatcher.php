<?php

namespace Laravel\Telescope\Watchers;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Support\Str;
use Laravel\Telescope\Tags;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Eloquent\Model;

class EventsWatcher extends Watcher
{
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
        if ($this->shouldIgnore($eventName)) {
            return;
        }

        $formattedPayload = $this->extractPayload($eventName, $payload);

        Telescope::recordEvent(IncomingEntry::make([
            'name' => $eventName,
            'payload' => empty($formattedPayload) ? null : $formattedPayload,
            'listeners' => $this->formatListeners($eventName),
        ])->tags($this->extractTags($eventName, $payload)));
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
    private function extractPayload($eventName, $payload)
    {
        if (class_exists($eventName)) {
            return $this->extractPayloadFromEventObject($payload[0]);
        }

        return collect($payload)->map(function ($value) {
            return is_object($value) ? [
                'class' => get_class($value),
                'properties' => json_decode(json_encode($value), true),
            ] : $value;
        })->toArray();
    }

    /**
     * Extract the payload and tags from the event object.
     *
     * @param  object  $event
     * @return array
     */
    private function extractPayloadFromEventObject($event)
    {
        return collect((new ReflectionClass($event))->getProperties())
            ->mapWithKeys(function ($property) use ($event) {
                $property->setAccessible(true);

                if (($value = $property->getValue($event)) instanceof Model) {
                    return [$property->getName() => get_class($value).':'.$value->getKey()];
                } elseif (is_object($value)) {
                    return [
                        $property->getName() => [
                            'class' => get_class($value),
                            'properties' => json_decode(json_encode($value), true)
                        ]
                    ];
                } else {
                    return [$property->getName() => json_decode(json_encode($value), true)];
                }
            })->toArray();
    }

    /**
     * Extract the tags from the event.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @return array
     */
    private function extractTags($eventName, $payload)
    {
        return class_exists($eventName)
                ? $this->extractTagsFromEventObject($payload[0])
                : [];
    }

    /**
     * Extract the tags from the event object.
     *
     * @param  object  $event
     * @return array
     */
    private function extractTagsFromEventObject($event)
    {
        return Tags::for($event);
    }

    /**
     * Format list of event listeners.
     *
     * @param  string  $eventName
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
            })->reject(function ($listener) {
                return Str::contains($listener, 'Laravel\\Telescope');
            })->values()->toArray();
    }

    /**
     * Format a closure-based listener.
     *
     * @param Closure  $listener
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
