<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

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
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $this->formatValue($event),
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
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
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
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'set',
            'key' => $event->key,
            'value' => $this->formatValue($event),
            'expiration' => $this->formatExpiration($event),
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
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'forget',
            'key' => $event->key,
        ]));
    }

    /**
     * Determine the value of an event.
     *
     * @param  mixed  $event
     * @return mixed
     */
    private function formatValue($event)
    {
        return (! $this->shouldHideValue($event))
            ? $event->value
            : '********';
    }

    /**
     * Determine if the event value should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldHideValue($event)
    {
        return Str::is(
            $this->options['hidden'] ?? [],
            $event->key
        );
    }

    /**
     * @param  \Illuminate\Cache\Events\KeyWritten  $event
     * @return mixed
     */
    protected function formatExpiration(KeyWritten $event)
    {
        return property_exists($event, 'seconds')
            ? $event->seconds
            : $event->minutes * 60;
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return Str::is(array_merge($this->options['ignore'] ?? [], [
            'illuminate:queue:restart',
            'framework/schedule*',
            'laravel:reverb:restart',
            'telescope:*',
        ]), $event->key);
    }
}
