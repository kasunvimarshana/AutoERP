<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the multi-tenant
    | system. Configure tenant isolation, database strategies, domain
    | handling, and organizational hierarchy settings here.
    |
    */

    'enabled' => env('MULTI_TENANCY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Strategy
    |--------------------------------------------------------------------------
    |
    | Supported: "single", "multi"
    | - single: All tenants share a single database with tenant_id scoping
    | - multi: Each tenant has their own database (future enhancement)
    |
    */

    'database_strategy' => env('TENANCY_DATABASE_STRATEGY', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are identified from incoming requests.
    | Multiple methods can be enabled simultaneously.
    |
    */

    'identification' => [
        'subdomain' => [
            'enabled' => env('TENANT_USE_SUBDOMAIN', true),
            'parameter' => 'subdomain',
        ],
        'domain' => [
            'enabled' => env('TENANT_USE_DOMAIN', false),
            'parameter' => 'domain',
        ],
        'header' => [
            'enabled' => env('TENANT_USE_HEADER', true),
            'parameter' => 'X-Tenant-ID',
        ],
        'session' => [
            'enabled' => env('TENANT_USE_SESSION', false),
            'parameter' => 'tenant_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization Hierarchy
    |--------------------------------------------------------------------------
    |
    | Configuration for hierarchical organizational structures.
    | Organizations can have parent-child relationships with inheritance.
    |
    */

    'organizations' => [
        'enabled' => env('TENANT_ORGANIZATIONS_ENABLED', true),
        'max_depth' => env('TENANT_ORG_MAX_DEPTH', 10),
        'inherit_settings' => env('TENANT_ORG_INHERIT_SETTINGS', true),
        'inherit_permissions' => env('TENANT_ORG_INHERIT_PERMISSIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Context
    |--------------------------------------------------------------------------
    |
    | Configure how tenant context is stored and accessed within the
    | application. Context is used to scope all queries and operations.
    |
    */

    'context' => [
        'cache_enabled' => env('TENANT_CONTEXT_CACHE', true),
        'cache_ttl' => env('TENANT_CONTEXT_CACHE_TTL', 3600), // 1 hour
        'strict_mode' => env('TENANT_STRICT_MODE', true), // Fail if no tenant found
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Enforce strict tenant isolation at various levels. When enabled,
    | attempts to access data from other tenants will be blocked.
    |
    */

    'isolation' => [
        'database' => true, // Enforce via query scopes
        'filesystem' => env('TENANT_ISOLATE_FILESYSTEM', true),
        'cache' => env('TENANT_ISOLATE_CACHE', true),
        'queue' => env('TENANT_ISOLATE_QUEUE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Provisioning
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic tenant provisioning and setup.
    |
    */

    'provisioning' => [
        'auto_provision' => env('TENANT_AUTO_PROVISION', false),
        'run_migrations' => env('TENANT_RUN_MIGRATIONS', true),
        'run_seeders' => env('TENANT_RUN_SEEDERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for tenant lifecycle events.
    |
    */

    'events' => [
        'enabled' => env('TENANT_EVENTS_ENABLED', true),
    ],
];
