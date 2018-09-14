<?php

use Laravel\Telescope\Watchers;

return [

    'ignoreCommands' => [],

    'storage' => [
        'database' => [
            'connection' => 'mysql',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Watchers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the configurations for each watcher that is
    | used by Telescope.
    |
    */

    'watchers' => [
        Watchers\CacheWatcher::class,
        Watchers\CommandWatcher::class,
        Watchers\EventWatcher::class,
        Watchers\ExceptionWatcher::class,
        Watchers\JobWatcher::class,
        Watchers\LogWatcher::class,
        Watchers\MailWatcher::class,
        Watchers\ModelWatcher::class,
        Watchers\NotificationWatcher::class,
        Watchers\QueryWatcher::class,
        Watchers\RedisWatcher::class,
        Watchers\RequestWatcher::class,
        Watchers\ScheduleWatcher::class,
    ],
];
