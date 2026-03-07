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
    | How long (in seconds) the TenantConfigService caches each tenant's
    | configuration before hitting the database again.
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
        'mail' => [
            'driver'       => env('MAIL_MAILER', 'smtp'),
            'from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'from_name'    => env('MAIL_FROM_NAME', 'SaaS Platform'),
        ],
        'payment' => [
            'gateway'    => 'stripe',
            'currency'   => 'USD',
            'test_mode'  => true,
        ],
        'notifications' => [
            'slack_enabled' => false,
            'push_enabled'  => false,
        ],
        'features' => [
            'sso'           => false,
            'two_factor'    => false,
            'api_access'    => true,
            'webhooks'      => false,
        ],
        'limits' => [
            'max_users'       => 5,
            'max_storage_gb'  => 1,
            'api_rate_limit'  => 60,
        ],
    ],

];
