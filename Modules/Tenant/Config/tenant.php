<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MULTI_TENANCY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Define how tenants are identified in requests
    |
    */

    'identification' => [
        'header' => 'X-Tenant-ID',
        'subdomain' => env('TENANT_USE_SUBDOMAIN', true),
        'domain' => env('TENANT_USE_DOMAIN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization Settings
    |--------------------------------------------------------------------------
    */

    'organizations' => [
        'max_depth' => 10,
        'types' => [
            'company' => 'Company',
            'division' => 'Division',
            'department' => 'Department',
            'team' => 'Team',
        ],
    ],
];
