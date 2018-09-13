<?php

namespace Laravel\Telescope;

trait RegistersWatchers
{
    /**
     * Register the configured Telescope watchers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected static function registerWatchers($app)
    {
        $watchers = [
            Watchers\CacheWatcher::class => config('telescope.watchers.cache.enabled'),
            Watchers\CommandWatcher::class => config('telescope.watchers.commands.enabled'),
            Watchers\EventWatcher::class => config('telescope.watchers.events.enabled'),
            Watchers\ExceptionWatcher::class => config('telescope.watchers.exceptions.enabled'),
            Watchers\JobWatcher::class => config('telescope.watchers.jobs.enabled'),
            Watchers\LogWatcher::class => config('telescope.watchers.logs.enabled'),
            Watchers\MailWatcher::class => config('telescope.watchers.mail.enabled'),
            Watchers\ModelWatcher::class => config('telescope.watchers.models.enabled'),
            Watchers\NotificationWatcher::class => config('telescope.watchers.notifications.enabled'),
            Watchers\QueryWatcher::class => config('telescope.watchers.queries.enabled'),
            Watchers\RedisWatcher::class => config('telescope.watchers.redis.enabled'),
            Watchers\RequestWatcher::class => config('telescope.watchers.requests.enabled'),
            Watchers\ScheduleWatcher::class => config('telescope.watchers.schedule.enabled'),
        ];

        foreach (array_keys(array_filter($watchers)) as $watcher) {
            (new $watcher)->register($app);
        }
    }
}
