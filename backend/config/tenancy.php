<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is for stancl/tenancy package. Multi-tenant database
    | separation is configured here.
    |
    | Note: The Core module provides its own Tenant model at
    | Modules\Core\Models\Tenant for custom multi-tenancy implementation.
    | This stancl/tenancy configuration is maintained for reference.
    |
    */

    'tenant_model' => \Stancl\Tenancy\Database\Models\Tenant::class,

    'id_generator' => \Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */

    'database' => [
        'based_on' => env('DB_CONNECTION', 'pgsql'),
        'prefix' => env('TENANT_DATABASE_PREFIX', 'tenant_'),
        'suffix' => env('TENANT_DATABASE_SUFFIX', ''),
        'template_tenant_connection' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    */

    'central_domains' => [
        '127.0.0.1',
        'localhost',
        env('APP_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    */

    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        // \Stancl\Tenancy\Features\UserImpersonation::class,
        \Stancl\Tenancy\Features\TelescopeTags::class,
        \Stancl\Tenancy\Features\TenantConfig::class,
        \Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    */

    'filesystem' => [
        'suffix_base' => env('TENANT_FILESYSTEM_SUFFIX_BASE', 'tenant'),
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'tenancy' => true,
        'prefixed_connections' => ['default'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'tag_base' => 'tenant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exempt Routes
    |--------------------------------------------------------------------------
    */

    'exempt_routes' => [
        '/health',
        '/api/health',
    ],
];
