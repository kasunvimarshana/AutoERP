<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the core module system
    |
    */

    'modules' => [
        'path' => base_path('modules'),
        'namespace' => 'Modules',
        'auto_discover' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    */

    'system' => [
        'name' => env('APP_NAME', 'Enterprise ERP/CRM'),
        'version' => '1.0.0',
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Settings
    |--------------------------------------------------------------------------
    */

    'multi_tenancy' => [
        'enabled' => env('MULTI_TENANCY_ENABLED', true),
        'database_strategy' => env('TENANCY_DATABASE_STRATEGY', 'single'), // single, multiple
        'tenant_column' => 'tenant_id',
    ],
];
