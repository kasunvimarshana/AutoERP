<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reporting Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Reporting module
    |
    */

    'enabled' => env('REPORTING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */
    'exports' => [
        'storage_disk' => env('REPORTING_STORAGE_DISK', 'local'),
        'storage_path' => env('REPORTING_STORAGE_PATH', 'exports'),
        'cleanup_days' => env('REPORTING_CLEANUP_DAYS', 7),
        'max_file_size' => env('REPORTING_MAX_FILE_SIZE', 10485760), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Execution
    |--------------------------------------------------------------------------
    */
    'execution' => [
        'timeout' => env('REPORTING_EXECUTION_TIMEOUT', 300), // 5 minutes
        'max_results' => env('REPORTING_MAX_RESULTS', 10000),
        'chunk_size' => env('REPORTING_CHUNK_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Reports
    |--------------------------------------------------------------------------
    */
    'scheduling' => [
        'enabled' => env('REPORTING_SCHEDULING_ENABLED', true),
        'queue' => env('REPORTING_QUEUE', 'reports'),
        'max_retries' => env('REPORTING_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    */
    'dashboards' => [
        'max_widgets' => env('REPORTING_MAX_WIDGETS', 20),
        'default_refresh_interval' => env('REPORTING_DEFAULT_REFRESH', 300), // 5 minutes
        'grid_columns' => env('REPORTING_GRID_COLUMNS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'cache_ttl' => env('REPORTING_ANALYTICS_CACHE_TTL', 3600), // 1 hour
        'default_date_range' => env('REPORTING_DEFAULT_DATE_RANGE', 30), // days
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Report Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'sales_summary' => [
            'name' => 'Sales Summary Report',
            'type' => 'sales',
            'format' => 'summary',
        ],
        'inventory_status' => [
            'name' => 'Inventory Status Report',
            'type' => 'inventory',
            'format' => 'table',
        ],
        'financial_overview' => [
            'name' => 'Financial Overview',
            'type' => 'financial',
            'format' => 'summary',
        ],
        'crm_pipeline' => [
            'name' => 'CRM Pipeline Report',
            'type' => 'crm',
            'format' => 'chart',
        ],
    ],
];
