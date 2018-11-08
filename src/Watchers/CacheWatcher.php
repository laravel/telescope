<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

class CacheWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
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
     * @param  \Illuminate\Cache\Events\CacheHit  $event
     * @return void
     */
    public function recordCacheHit(CacheHit $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $event->value,
        ]));
    }

    /**
     * Record a missing cache key.
     *
     * @param  \Illuminate\Cache\Events\CacheMissed  $event
     * @return void
     */
    public function recordCacheMissed(CacheMissed $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'missed',
            'key' => $event->key,
        ]));
    }

    /**
     * Record a cache key was updated.
     *
     * @param  \Illuminate\Cache\Events\KeyWritten  $event
     * @return void
     */
    public function recordKeyWritten(KeyWritten $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'set',
            'key' => $event->key,
            'value' => $event->value,
            'expiration' => $event->minutes,
        ]));
    }

    /**
     * Record a cache key was forgotten / removed.
     *
     * @param  \Illuminate\Cache\Events\KeyForgotten  $event
     * @return void
     */
    public function recordKeyForgotten(KeyForgotten $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'forget',
            'key' => $event->key,
        ]));
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return Str::is([
            'illuminate:queue:restart',
            'framework/schedule*',
            'telescope:*',
        ], $event->key);
    }
}
