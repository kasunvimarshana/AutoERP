<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution Mode
    |--------------------------------------------------------------------------
    | Options: 'header', 'subdomain', 'domain'
    */
    'mode' => env('TENANT_MODE', 'header'),

    /*
    | Header name used to pass tenant identifier
    */
    'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

    /*
    | Subdomain suffix to strip when resolving tenant
    */
    'subdomain_suffix' => env('TENANT_SUBDOMAIN_SUFFIX', '.example.com'),

    /*
    | Cache TTL in seconds for tenant lookup results
    */
    'cache_ttl' => (int) env('TENANT_CACHE_TTL', 3600),

    /*
    | Default tenant ID (used in CLI / queue jobs when no header is present)
    */
    'default_id' => env('TENANT_DEFAULT_ID', null),

    /*
    | Routes and paths that bypass tenant resolution
    */
    'bypass_paths' => [
        'health',
        'health/*',
    ],

    /*
    | Plans and their limits
    */
    'plans' => [
        'starter' => [
            'max_users'    => 5,
            'max_products' => 100,
            'features'     => ['basic_inventory', 'basic_orders'],
        ],
        'professional' => [
            'max_users'    => 50,
            'max_products' => 5000,
            'features'     => ['basic_inventory', 'advanced_orders', 'webhooks', 'reports'],
        ],
        'enterprise' => [
            'max_users'    => -1,  // unlimited
            'max_products' => -1,
            'features'     => ['*'],
        ],
    ],
];
