<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-tenancy settings for the platform.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | How to resolve the current tenant from an incoming request.
    | Options: 'subdomain', 'header', 'jwt_claim'
    |
    */
    'resolution_strategy' => env('TENANT_RESOLUTION', 'header'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Header Name
    |--------------------------------------------------------------------------
    |
    | The HTTP header used to identify the tenant when strategy = 'header'.
    |
    */
    'header_name' => 'X-Tenant-ID',

    /*
    |--------------------------------------------------------------------------
    | Current Tenant ID (Testing / Seeding)
    |--------------------------------------------------------------------------
    |
    | Set this in testing/seeding environments to bypass header-based resolution.
    |
    */
    'current_tenant_id' => env('TENANT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Database Isolation Model
    |--------------------------------------------------------------------------
    |
    | Default: shared DB with row-level isolation via tenant_id.
    |
    */
    'db_isolation_model' => 'shared_db_shared_schema',
];
