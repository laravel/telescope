<?php

use Laravel\Telescope\Watchers;

return [
    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => 'mysql',
        ]
    ],

    'watchers' => [
        Watchers\CacheWatcher::class,
        Watchers\CommandWatcher::class,
        Watchers\DumpWatcher::class,
        Watchers\EventWatcher::class,
        Watchers\ExceptionWatcher::class,
        Watchers\JobWatcher::class,
        Watchers\LogWatcher::class,
        Watchers\MailWatcher::class,
        Watchers\ModelWatcher::class,
        Watchers\NotificationWatcher::class,
        Watchers\QueryWatcher::class => [
            'enabled' => true,
            'slow' => 100,
        ],
        Watchers\RedisWatcher::class,
        Watchers\RequestWatcher::class,
        Watchers\ScheduleWatcher::class,
    ],
];
