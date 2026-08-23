<?php

namespace Laravel\Telescope;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

class ExtractTags
{
    /**
     * Get the tags for the given object.
     *
     * @param  mixed  $target
     * @return array
     */
    public static function from($target)
    {
        if ($tags = static::explicitTags([$target])) {
            return $tags;
        }

        return static::modelsFor([$target])->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    /**
     * Determine the tags for the given job.
     *
     * @param  mixed  $job
     * @return array
     */
    public static function fromJob($job)
    {
        if ($tags = static::extractExplicitTags($job)) {
            return $tags;
        }

        return static::modelsFor(static::targetsFor($job))->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    /**
     * Determine the tags for the given array.
     *
     * @param  array  $data
     * @return array
     */
    public static function fromArray(array $data)
    {
        return collect($data)->map(function ($value) {
            return static::resolveValue($value);
        })->collapse()->filter()->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    /**
     * Extract tags from job object.
     *
     * @param  mixed  $job
     * @return array
     */
    protected static function extractExplicitTags($job)
    {
        return $job instanceof CallQueuedListener
            ? static::tagsForListener($job)
            : static::explicitTags(static::targetsFor($job));
    }

    /**
     * Determine tags for the given queued listener.
     *
     * @param  mixed  $job
     * @return array
     */
    protected static function tagsForListener($job)
    {
        $listener = static::extractListener($job);
        $event = static::extractEvent($job);

        return collect([
            static::tagsForListenerInstance($listener, $event),
            static::from($event),
        ])->collapse()->unique()->toArray();
    }

    /**
     * Determine the explicit or model-based tags for the given listener instance.
     *
     * Horizon documents queued listener tagging with an event-aware signature
     * such as `tags(OrderShipped $event): array`, so we can't blindly call
     * `tags()` without arguments as we do for other taggable targets.
     *
     * @param  mixed  $listener
     * @param  mixed  $event
     * @return array
     */
    protected static function tagsForListenerInstance($listener, $event)
    {
        if (method_exists($listener, 'tags')) {
            if ((new ReflectionMethod($listener, 'tags'))->getNumberOfParameters() > 0) {
                return $event instanceof stdClass ? [] : $listener->tags($event);
            }

            if ($tags = $listener->tags()) {
                return $tags;
            }
        }

        return static::modelsFor([$listener])->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    /**
     * Determine tags for the given job.
     *
     * @param  array  $targets
     * @return array
     */
    protected static function explicitTags(array $targets)
    {
        return collect($targets)->map(function ($target) {
            return method_exists($target, 'tags') ? $target->tags() : [];
        })->collapse()->unique()->all();
    }

    /**
     * Get the actual target for the given job.
     *
     * @param  mixed  $job
     * @return array
     */
    protected static function targetsFor($job)
    {
        return match (true) {
            $job instanceof BroadcastEvent => [$job->event],
            $job instanceof CallQueuedListener => [static::extractEvent($job)],
            $job instanceof SendQueuedMailable => [$job->mailable],
            $job instanceof SendQueuedNotifications => [$job->notification],
            default => [$job],
        };
    }

    /**
     * Get the models from the given object.
     *
     * @param  array  $targets
     * @return \Illuminate\Support\Collection
     */
    protected static function modelsFor(array $targets)
    {
        return collect($targets)->map(function ($target) {
            return collect((new ReflectionClass($target))->getProperties())->map(function ($property) use ($target) {
                if (PHP_VERSION_ID < 80500) {
                    $property->setAccessible(true);
                }

                if (PHP_VERSION_ID < 70400 || ! is_object($target) || $property->isInitialized($target)) {
                    return static::resolveValue($property->getValue($target));
                }
            })->collapse()->filter();
        })->collapse()->unique();
    }

    /**
     * Extract the listener from a queued job.
     *
     * @param  mixed  $job
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected static function extractListener($job)
    {
        return (new ReflectionClass($job->class))->newInstanceWithoutConstructor();
    }

    /**
     * Extract the event from a queued job.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected static function extractEvent($job)
    {
        return isset($job->data[0]) && is_object($job->data[0])
            ? $job->data[0]
            : new stdClass;
    }

    /**
     * Resolve the given value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection|null
     */
    protected static function resolveValue($value)
    {
        return match (true) {
            $value instanceof Model => collect([$value]),
            $value instanceof Collection => $value->flatten(),
            default => null,
        };
    }
}
