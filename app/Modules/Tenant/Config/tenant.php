<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'resolution' => [
        'required' => (bool) env('TENANT_RESOLUTION_REQUIRED', true),
        'enforce_authenticated_tenant_match' => (bool) env('TENANT_ENFORCE_AUTHENTICATED_TENANT_MATCH', true),
        'local_fallback_enabled' => (bool) env('TENANT_LOCAL_FALLBACK_ENABLED', false),
        'local_fallback_domain' => env('TENANT_LOCAL_FALLBACK_DOMAIN', null),
        'local_fallback_tenant_code' => env('TENANT_LOCAL_FALLBACK_TENANT_CODE', env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')),
    ],
    'defaults' => [
        'is_isolated' => true,
    ],
];
