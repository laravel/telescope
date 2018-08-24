<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

class CacheWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CacheHit::class, [$this, 'recordAKeyWasFound']);
        $app['events']->listen(CacheMissed::class, [$this, 'recordAMissingKey']);
        $app['events']->listen(KeyWritten::class, [$this, 'recordAKeyWasUpdated']);
        $app['events']->listen(KeyForgotten::class, [$this, 'recordAkeyWasRemoved']);
    }

    /**
     * Record a cache key was found.
     * 
     * @param \Illuminate\Cache\Events\CacheHit $event
     * @return void
     */
    public function recordAKeyWasFound(CacheHit $event)
    {
        Telescope::record(6, [
            'type' => 'hit',
            'key' => $event->key,
            'value' => $event->value,
        ]);
    }

    /**
     * Record a missing cache key.
     *
     * @param \Illuminate\Cache\Events\CacheMissed $event
     * @return void
     */
    public function recordAMissingKey(CacheMissed $event)
    {
        Telescope::record(6, [
            'type' => 'missed',
            'key' => $event->key,
        ]);
    }

    /**
     * Record a cache key was updated.
     *
     * @param \Illuminate\Cache\Events\KeyWritten $event
     * @return void
     */
    public function recordAKeyWasUpdated(KeyWritten $event)
    {
        Telescope::record(6, [
            'type' => 'put',
            'key' => $event->key,
            'value' => $event->value,
            'expiration' => $event->minutes,
        ]);
    }

    /**
     * Record a cache key was removed.
     *
     * @param \Illuminate\Cache\Events\KeyForgotten $event
     * @return void
     */
    public function recordAkeyWasRemoved(KeyForgotten $event)
    {
        Telescope::record(6, [
            'type' => 'removed',
            'key' => $event->key,
        ]);
    }
}