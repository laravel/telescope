<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Entry;
use Laravel\Telescope\Telescope;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

class CacheWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CacheHit::class, [$this, 'recordCacheHit']);
        $app['events']->listen(CacheMissed::class, [$this, 'recordCacheMissed']);
        $app['events']->listen(KeyWritten::class, [$this, 'recordKeyWritten']);
        $app['events']->listen(KeyForgotten::class, [$this, 'recordKeyForgotten']);
    }

    /**
     * Record a cache key was found.
     *
     * @param \Illuminate\Cache\Events\CacheHit $event
     * @return void
     */
    public function recordCacheHit(CacheHit $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry(Entry::make([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $event->value,
        ])->withTags([$event->key]));
    }

    /**
     * Record a missing cache key.
     *
     * @param \Illuminate\Cache\Events\CacheMissed $event
     * @return void
     */
    public function recordCacheMissed(CacheMissed $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry(Entry::make([
            'type' => 'missed',
            'key' => $event->key,
        ])->withTagss([$event->key]));
    }

    /**
     * Record a cache key was updated.
     *
     * @param \Illuminate\Cache\Events\KeyWritten $event
     * @return void
     */
    public function recordKeyWritten(KeyWritten $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry(Entry::make([
            'type' => 'put',
            'key' => $event->key,
            'value' => $event->value,
            'expiration' => $event->minutes,
        ])->withTags([$event->key]));
    }

    /**
     * Record a cache key was removed.
     *
     * @param \Illuminate\Cache\Events\KeyForgotten $event
     * @return void
     */
    public function recordKeyForgotten(KeyForgotten $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry(Entry::make([
            'type' => 'removed',
            'key' => $event->key,
        ])->withTags([$event->key]));
    }

    /**
     * Determine if the event should be recorded.
     *
     * @param  mixed $event
     * @return bool
     */
    private function shouldRecord($event)
    {
        return $event->key != 'illuminate:queue:restart	';
    }
}
