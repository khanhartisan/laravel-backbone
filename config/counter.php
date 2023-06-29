<?php

// Counter configuration
return [
    'default_recorder' => 'redis',

    'default_store' => 'database',

    'recorders'=> [
        'redis' => [
            'connection' => env('COUNTER_RECORDER_REDIS_CONNECTION', 'cache'),
            'expiration' => 86400
        ]
    ],

    'stores' => [
        'database' => [
            'connection' => env('COUNTER_STORE_DATABASE_CONNECTION', 'mysql')
        ]
    ]
];