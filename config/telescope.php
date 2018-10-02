<?php

use Laravel\Telescope\Watchers;

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Telescope's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

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
    | The following array lists the "watchers" that will be registered with
    | Telescope. The watchers gather the application's profile data when
    | a request or task is executed. Feel free to customize this list.
    |
    */

    'watchers' => [
        Watchers\CacheWatcher::class => true,
        Watchers\CommandWatcher::class => true,
        Watchers\DumpWatcher::class => true,
        Watchers\EventWatcher::class => true,
        Watchers\ExceptionWatcher::class => true,
        Watchers\JobWatcher::class => true,
        Watchers\LogWatcher::class => true,
        Watchers\MailWatcher::class => true,
        Watchers\ModelWatcher::class => true,
        Watchers\NotificationWatcher::class => true,

        Watchers\QueryWatcher::class => [
            'enabled' => true,
            'slow' => 100,
        ],

        Watchers\RedisWatcher::class => true,
        Watchers\RequestWatcher::class => true,
        Watchers\ScheduleWatcher::class => true,
    ],
];
