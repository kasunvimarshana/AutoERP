<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Tenant Plan
    |--------------------------------------------------------------------------
    |
    | The plan assigned to a new tenant when none is specified.
    |
    */
    'default_plan' => env('TENANT_DEFAULT_PLAN', 'free'),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Status
    |--------------------------------------------------------------------------
    |
    | The status assigned to a new tenant when none is specified.
    |
    */
    'default_status' => env('TENANT_DEFAULT_STATUS', 'trial'),

    /*
    |--------------------------------------------------------------------------
    | Trial Period (Days)
    |--------------------------------------------------------------------------
    |
    | Number of days a new tenant is placed in trial before requiring a plan.
    |
    */
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Org Unit Types
    |--------------------------------------------------------------------------
    |
    | Allowed types for organisational units.
    |
    */
    'org_unit_types' => [
        'company',
        'division',
        'department',
        'branch',
        'warehouse',
        'store',
        'other',
    ],
];
