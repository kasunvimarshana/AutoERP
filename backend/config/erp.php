<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Configuration
    |--------------------------------------------------------------------------
    |
    | Dynamic runtime configuration for the ERP system
    |
    */

    'features' => [
        'multi_currency' => env('ERP_MULTI_CURRENCY', true),
        'multi_language' => env('ERP_MULTI_LANGUAGE', true),
        'multi_warehouse' => env('ERP_MULTI_WAREHOUSE', true),
        'audit_logging' => env('ERP_AUDIT_LOGGING', true),
        'notifications' => env('ERP_NOTIFICATIONS', true),
        'workflows' => env('ERP_WORKFLOWS', true),
    ],

    'modules' => [
        'enabled' => [
            'base',
            'iam',
            'inventory',
        ],
    ],

    'multi_tenancy' => [
        'enabled' => env('ERP_MULTI_TENANCY', true),
        'isolation_strategy' => env('ERP_TENANT_ISOLATION', 'row_level'), // row_level, schema, database
    ],

    'security' => [
        'mfa_enabled' => env('ERP_MFA_ENABLED', false),
        'password_expiry_days' => env('ERP_PASSWORD_EXPIRY_DAYS', 90),
        'session_timeout_minutes' => env('ERP_SESSION_TIMEOUT', 120),
    ],

    'ui' => [
        'theme' => env('ERP_THEME', 'default'),
        'locale' => env('APP_LOCALE', 'en'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'currency' => env('ERP_CURRENCY', 'USD'),
        'date_format' => env('ERP_DATE_FORMAT', 'Y-m-d'),
        'time_format' => env('ERP_TIME_FORMAT', 'H:i:s'),
    ],

    'inventory' => [
        'costing_method' => env('ERP_INVENTORY_COSTING', 'fifo'), // fifo, lifo, fefo, avg
        'negative_stock_allowed' => env('ERP_NEGATIVE_STOCK', false),
        'batch_tracking' => env('ERP_BATCH_TRACKING', true),
        'serial_tracking' => env('ERP_SERIAL_TRACKING', true),
    ],

    'notifications' => [
        'channels' => [
            'mail' => env('ERP_NOTIFY_MAIL', true),
            'database' => env('ERP_NOTIFY_DATABASE', true),
            'push' => env('ERP_NOTIFY_PUSH', false),
        ],
    ],
];
