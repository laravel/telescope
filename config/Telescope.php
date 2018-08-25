<?php

return [

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
        'cache' => [
            'enabled' => true,
        ],

        'events' => [
            'enabled' => false,
        ],

        'log' => [
            'enabled' => true,
        ],

        'mail' => [
            'enabled' => true,
        ],

        'notifications' => [
            'enabled' => true,
        ],

        'queue' => [
            'enabled' => true,
        ],
    ],
];
