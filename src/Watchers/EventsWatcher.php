<?php

namespace Laravel\Telescope\Watchers;

use Closure;
use ReflectionClass;
use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
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
     * Record the given event.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return void
     */
    public function recordEvent($eventName, $payload)
    {
        if (! $this->eventShouldBeRecorded($eventName)) {
            return;
        }

        list($payload, $tags) = $this->extractPayloadAndTags($eventName, $payload);

        Telescope::recordEvent([
            'event_name' => $eventName,
            'event_payload' => $payload,
            'listeners' => $this->formatListeners($eventName),
        ], $tags);
    }

    /**
     * Determine if the event should be recorded.
     *
     * @param  string $eventName
     * @return bool
     */
    private function eventShouldBeRecorded($eventName)
    {
        if (Telescope::ignoresFrameworkEvents() && $this->eventIsFiredByTheFramework($eventName)) {
            return false;
        }

        return true;
    }

    /**
     * Extract the payload and tags from the event.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return array
     */
    private function extractPayloadAndTags($eventName, $payload)
    {
        if (! $this->eventIsAnObject($eventName)) {
            return [$this->formatRawPayload($payload), []];
        }

        return $this->extractPayloadAndTagsFromEventObject($payload[0]);
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
                    $tags[] = $model = get_class($value).':'.$value->getKey();

                    return [$property->getName() => $model];
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
     * Determine if the event was fired internally by Laravel.
     *
     * @param  string $eventName
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
     * Determine if the event is an object.
     *
     * @param  string $eventName
     * @return bool
     */
    public function eventIsAnObject($eventName)
    {
        return Str::is(['App\*', 'Illuminate\*'], $eventName);
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
                $listener = (new \ReflectionFunction($listener))->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return (str_contains($listener, '@') ? $listener : $listener.'@handle');
                } elseif (is_array($listener)) {
                    return get_class($listener[0]).'@'.$listener[1];
                } else {
                    return $this->formatClosureListener($listener);
                }
            })
            ->reject(function ($listener) {
                return str_contains($listener, 'Laravel\\Telescope');
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
        $listener = (new \ReflectionFunction($listener));

        return sprintf('Closure at %s[%s:%s]',
            $listener->getFileName(), $listener->getStartLine(), $listener->getEndLine()
        );
    }
}
