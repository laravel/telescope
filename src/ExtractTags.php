<?php

namespace Laravel\Telescope;

use stdClass;
use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
        $models = collect($data)->map(function ($value) {
            if ($value instanceof Model) {
                return [$value];
            } elseif ($value instanceof EloquentCollection) {
                return $value->flatten();
            }
        })->collapse()->filter();

        return $models->map(function ($model) {
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
        return collect(
            [static::extractListener($job), static::extractEvent($job),
        ])->map(function ($job) {
            return static::from($job);
        })->collapse()->unique()->toArray();
    }

    /**
     * Determine tags for the given job.
     *
     * @param  array  $targets
     * @return mixed
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
        switch (true) {
            case $job instanceof BroadcastEvent:
                return [$job->event];
            case $job instanceof CallQueuedListener:
                return [static::extractEvent($job)];
            case $job instanceof SendQueuedMailable:
                return [$job->mailable];
            case $job instanceof SendQueuedNotifications:
                return [$job->notification];
            default:
                return [$job];
        }
    }

    /**
     * Get the models from the given object.
     *
     * @param  array  $targets
     * @return \Illuminate\Support\Collection
     */
    protected static function modelsFor(array $targets)
    {
        $models = [];

        foreach ($targets as $target) {
            $models[] = collect((new ReflectionClass($target))->getProperties())->map(function ($property) use ($target) {
                $property->setAccessible(true);

                $value = $property->getValue($target);

                if ($value instanceof Model) {
                    return [$value];
                } elseif ($value instanceof EloquentCollection) {
                    return $value->flatten();
                }
            })->collapse()->filter()->all();
        }

        return collect(Arr::collapse($models))->unique();
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
}
