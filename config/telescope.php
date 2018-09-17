<?php

use Laravel\Telescope\Watchers;

return [
    'driver' => env('TELESCOPE_DRIVER', 'redis'),

    'storage' => [
        'database' => [
            'connection' => 'mysql',
        ],

        'redis' => [
            'connection' => 'default',
            'lifetime' => 172800,
        ]
    ],

    'ignoreCommands' => [],

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
