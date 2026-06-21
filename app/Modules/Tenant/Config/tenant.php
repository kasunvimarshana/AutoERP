<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'resolution' => [
        'local_fallback_enabled' => (bool) env('TENANT_LOCAL_FALLBACK_ENABLED', false),
        'local_fallback_domain' => env('TENANT_LOCAL_FALLBACK_DOMAIN', null),
        'local_fallback_tenant_code' => env('TENANT_LOCAL_FALLBACK_TENANT_CODE', env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')),
        'signals' => [
            'id_input_keys' => ['tenant_id'],
            'id_route_keys' => ['tenant_id'],
            'id_header_keys' => ['X-Tenant-Id'],
            'code_input_keys' => ['tenant_code'],
            'code_route_keys' => ['tenant', 'tenant_code'],
            'code_header_keys' => ['X-Tenant-Code'],
            'uuid_input_keys' => ['tenant_uuid'],
            'uuid_route_keys' => ['tenant_uuid'],
            'uuid_header_keys' => ['X-Tenant-Uuid'],
            'isolation_key_input_keys' => ['tenant_isolation_key'],
            'isolation_key_route_keys' => ['tenant_isolation_key'],
            'isolation_key_header_keys' => ['X-Tenant-Isolation-Key'],
            'domain_input_keys' => ['tenant_domain'],
            'domain_header_keys' => ['X-Tenant-Domain'],
            'application_input_keys' => ['application_id', 'app_id', 'client_id'],
            'application_header_keys' => ['X-Application-Id', 'X-App-Id', 'X-Client-Id'],
        ],
    ],
    'defaults' => [
        'is_isolated' => true,
    ],
];
