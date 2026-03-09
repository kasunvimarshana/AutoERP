<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-tenancy runtime behavior.
    |
    */
    'identification' => env('TENANT_IDENTIFICATION', 'header'), // header, subdomain, path
    'header' => 'X-Tenant-ID',
    'default_connection' => env('TENANT_DEFAULT_CONNECTION', 'mysql'),
    'cache_ttl' => env('TENANT_CACHE_TTL', 3600),

];
