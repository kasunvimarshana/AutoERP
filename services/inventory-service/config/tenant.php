<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification Header
    |--------------------------------------------------------------------------
    | The HTTP header used to identify the tenant for each request.
    */
    'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation Strategy
    |--------------------------------------------------------------------------
    | 'database' - Each tenant has its own database.
    | 'schema'   - Each tenant has its own schema within a shared database.
    | 'row'      - Row-level isolation using tenant_id column (shared tables).
    */
    'isolation' => env('TENANT_ISOLATION', 'row'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Prefix
    |--------------------------------------------------------------------------
    | Prefix used when creating per-tenant databases or schemas.
    */
    'db_prefix' => env('TENANT_DB_PREFIX', 'tenant_'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Cache Prefix
    |--------------------------------------------------------------------------
    | Cache key prefix to namespace per-tenant cache entries.
    */
    'cache_prefix' => env('TENANT_CACHE_PREFIX', 'tenant:'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Connection Template
    |--------------------------------------------------------------------------
    | The database connection config key used as a template when dynamically
    | creating per-tenant database connections.
    */
    'connection_template' => 'tenant_template',

    /*
    |--------------------------------------------------------------------------
    | Tenant Middleware Bypass Paths
    |--------------------------------------------------------------------------
    | Request paths that bypass tenant resolution (e.g., health checks).
    */
    'bypass_paths' => [
        'api/v1/health',
        'api/health',
        'health',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    | The model used to look up tenant information (optional; for validation).
    */
    'model' => null,

];
