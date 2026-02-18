<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the modular architecture of AutoERP.
    |
    */

    'path' => base_path('modules'),

    'namespace' => 'Modules',

    'enabled' => [
        'Core',
        'IAM',
        'Accounting',
        'Inventory',
        'Sales',
        'Purchasing',
        'HR',
        'Manufacturing',
        'Analytics',
    ],

    'auto_discover' => true,

    'cache' => [
        'enabled' => env('MODULES_CACHE_ENABLED', true),
        'ttl' => env('MODULES_CACHE_TTL', 3600),
    ],
];
