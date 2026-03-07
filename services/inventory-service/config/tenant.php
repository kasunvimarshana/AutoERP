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
    | How long (in seconds) tenant context is cached before re-resolution.
    |
    */

    'config_cache_ttl' => (int) env('TENANT_CONFIG_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Default quantity below which an inventory item is considered "low stock".
    | Individual items can override this via their own threshold column if added.
    |
    */

    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),

];
