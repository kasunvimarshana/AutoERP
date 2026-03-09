<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification Drivers
    |--------------------------------------------------------------------------
    | subdomain: resolve from request subdomain (e.g. acme.saas.local)
    | header:    resolve from X-Tenant-ID header
    | jwt:       resolve from JWT 'tid' claim
    | path:      resolve from URL path segment /tenant/{id}/...
    | body:      resolve from request body field 'tenant_id'
    */

    'identification_driver' => env('TENANT_IDENTIFICATION_DRIVER', 'subdomain'),

    'identification_drivers' => ['subdomain', 'header', 'jwt', 'body'],

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    | Domains that are NOT tenant domains (no tenant context required).
    */

    'central_domains' => array_filter(
        explode(',', env('TENANT_CENTRAL_DOMAINS', 'localhost,auth-service.local'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Subdomain Configuration
    |--------------------------------------------------------------------------
    */

    'subdomain_column' => env('TENANT_SUBDOMAIN_COLUMN', 'subdomain'),

    'base_domain' => env('TENANT_BASE_DOMAIN', 'saas.local'),

    /*
    |--------------------------------------------------------------------------
    | Header Configuration
    |--------------------------------------------------------------------------
    */

    'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

    /*
    |--------------------------------------------------------------------------
    | Database Isolation Strategy
    |--------------------------------------------------------------------------
    | schema:   Each tenant gets their own PostgreSQL schema (recommended)
    | database: Each tenant gets their own database
    | prefix:   Shared database with table prefixes
    */

    'db_strategy' => env('TENANT_DB_STRATEGY', 'schema'),

    'db_prefix' => env('TENANT_DB_PREFIX', 'tenant_'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Connection Template
    |--------------------------------------------------------------------------
    */

    'db_connection_template' => [
        'driver'   => 'pgsql',
        'host'     => env('DB_HOST', 'postgres'),
        'port'     => env('DB_PORT', 5432),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => 'utf8',
        'prefix'   => '',
        'schema'   => 'public',
        'sslmode'  => 'prefer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Cache
    |--------------------------------------------------------------------------
    */

    'cache_ttl'    => 3600,
    'cache_prefix' => 'tenant_',

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */

    'model' => App\Domain\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Features
    |--------------------------------------------------------------------------
    | Default feature flags available for tenants.
    */

    'default_features' => [
        'multi_factor_auth' => false,
        'device_tracking'   => true,
        'audit_log'         => true,
        'rbac'              => true,
        'abac'              => false,
        'sso'               => false,
        'api_access'        => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans & Limits
    |--------------------------------------------------------------------------
    */

    'plans' => [
        'free'       => ['users' => 5,    'api_calls' => 1000,  'storage_mb' => 100],
        'starter'    => ['users' => 25,   'api_calls' => 10000, 'storage_mb' => 1024],
        'pro'        => ['users' => 100,  'api_calls' => 100000,'storage_mb' => 10240],
        'enterprise' => ['users' => -1,   'api_calls' => -1,    'storage_mb' => -1],
    ],

];
