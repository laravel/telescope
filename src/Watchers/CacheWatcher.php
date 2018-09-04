<?php

namespace Laravel\Telescope\Watchers;

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
        $app['events']->listen(CacheHit::class, [$this, 'recordFoundKey']);
        $app['events']->listen(CacheMissed::class, [$this, 'recordMissingKey']);
        $app['events']->listen(KeyWritten::class, [$this, 'recordUpdatedKey']);
        $app['events']->listen(KeyForgotten::class, [$this, 'recordRemovedKey']);
    }

    /**
     * Record a cache key was found.
     *
     * @param \Illuminate\Cache\Events\CacheHit $event
     * @return void
     */
    public function recordFoundKey(CacheHit $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $event->value,
        ], [$event->key]);
    }

    /**
     * Record a missing cache key.
     *
     * @param \Illuminate\Cache\Events\CacheMissed $event
     * @return void
     */
    public function recordMissingKey(CacheMissed $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry([
            'type' => 'missed',
            'key' => $event->key,
        ], [$event->key]);
    }

    /**
     * Record a cache key was updated.
     *
     * @param \Illuminate\Cache\Events\KeyWritten $event
     * @return void
     */
    public function recordUpdatedKey(KeyWritten $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry([
            'type' => 'put',
            'key' => $event->key,
            'value' => $event->value,
            'expiration' => $event->minutes,
        ], [$event->key]);
    }

    /**
     * Record a cache key was removed.
     *
     * @param \Illuminate\Cache\Events\KeyForgotten $event
     * @return void
     */
    public function recordRemovedKey(KeyForgotten $event)
    {
        if (! $this->shouldRecord($event)) {
            return;
        }

        Telescope::recordCacheEntry([
            'type' => 'removed',
            'key' => $event->key,
        ], [$event->key]);
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
