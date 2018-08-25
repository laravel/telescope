<?php

namespace Laravel\Telescope\Watchers;

use Closure;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Queue\Jobs\Job;
use Laravel\Telescope\Telescope;
use Illuminate\Database\Eloquent\Model;

class EventsWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen('*', [$this, 'recordTheEvent']);
    }

    /**
     * Record the given event.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return void
     */
    public function recordTheEvent($eventName, $payload)
    {
        if (! $this->eventShouldBeRecorded($eventName)) {
            return;
        }

        Telescope::record(5, [
            'event_name' => $eventName,
            'event_payload' => $this->formatEventPayload($eventName, $payload),
            'listeners' => $this->formatListeners($eventName),
        ]);
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
     * Format the event payload.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return array
     */
    private function formatEventPayload($eventName, $payload)
    {
        return $this->eventIsAnObject($eventName)
            ? $this->formatEventObject($payload[0]) : $this->formatRawPayload($payload);
    }

    /**
     * Format the event object.
     *
     * @param  object $payload
     * @return array
     */
    private function formatEventObject($event)
    {
        return collect((new ReflectionClass($event))->getProperties())
            ->mapWithKeys(function ($property) use ($event) {
                return [
                    $property->getName() => $this->formatEventObjectProperty($property->getValue($event))
                ];
            })->toArray();
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
     * Format an event object property.
     *
     * @param  mixed $value
     * @return mixed
     */
    private function formatEventObjectProperty($value)
    {
        if ($value instanceof Model) {
            return get_class($value).':'.$value->getKey();
        } elseif ($value instanceOf Job) {
            return [
                'jobId' => $value->getJobId(),
                'queue' => $value->getQueue(),
                'payload' => $value->payload(),
            ];
        } elseif (is_object($value)) {
            return json_decode(json_encode($value), true);
        }

        return $value;
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