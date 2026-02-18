<?php

return [
    'name' => 'Core',
    'version' => '1.0.0',
    'description' => 'Core module providing base classes, interfaces, and shared functionality for the entire system',

    /*
    |--------------------------------------------------------------------------
    | Module Settings
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'pagination' => [
            'per_page' => 15,
            'max_per_page' => 100,
        ],
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // 1 hour
        ],
    ],
];
