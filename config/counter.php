<?php

// Counter configuration
return [
    'default_recorder' => env('COUNTER_DEFAULT_RECORDER', 'redis'),

    'default_store' => env('COUNTER_DEFAULT_STORE', 'database'),

    'recorders'=> [
        'redis' => [
            'connection' => env('COUNTER_RECORDER_REDIS_CONNECTION', 'cache'),
            'expiration' => env('COUNTER_RECORDER_REDIS_EXPIRATION', 86400)
        ]
    ],

    'stores' => [
        'database' => [
            'connection' => env('COUNTER_STORE_DATABASE_CONNECTION', 'mysql'),
            'table' => env('COUNTER_STORE_DATABASE_TABLE', 'counter'),
        ]
    ]
];