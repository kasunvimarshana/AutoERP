<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Header
    |--------------------------------------------------------------------------
    |
    | The HTTP header used to identify the current tenant in API requests.
    |
    */

    'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

    /*
    |--------------------------------------------------------------------------
    | Config Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) tenant config is cached before re-fetching.
    |
    */

    'config_cache_ttl' => (int) env('TENANT_CONFIG_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Default Config Sections
    |--------------------------------------------------------------------------
    |
    | Fallback values used when a tenant has not yet configured a section.
    |
    */

    'defaults' => [
        'features' => [
            'api_access' => true,
            'webhooks'   => false,
        ],
        'limits' => [
            'max_products'   => 1000,
            'api_rate_limit' => 60,
        ],
    ],

];
