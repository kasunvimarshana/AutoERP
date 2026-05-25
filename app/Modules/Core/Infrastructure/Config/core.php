<?php

declare(strict_types=1);

return [
    'file_storage' => [
        'default_disk' => env('CORE_FILE_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    ],
    'slug' => [
        'fallback' => env('CORE_SLUG_FALLBACK', 'n-a'),
    ],
    'current_user' => [
        'middleware_alias' => env('CORE_CURRENT_USER_MIDDLEWARE_ALIAS', 'current.user'),
        'required' => (bool) env('CORE_CURRENT_USER_REQUIRED', true),
        'request_attribute' => env('CORE_CURRENT_USER_REQUEST_ATTRIBUTE', 'current_user'),
        'id_attribute' => env('CORE_CURRENT_USER_ID_ATTRIBUTE', 'current_user_id'),
        'guard_attribute' => env('CORE_CURRENT_USER_GUARD_ATTRIBUTE', 'current_user_guard'),
        'provider_attribute' => env('CORE_CURRENT_USER_PROVIDER_ATTRIBUTE', 'current_user_provider'),
        'tenant_attribute' => env('CORE_CURRENT_TENANT_ATTRIBUTE', 'current_tenant_id'),
        'organization_unit_attribute' => env('CORE_CURRENT_ORGANIZATION_UNIT_ATTRIBUTE', 'current_organization_unit_id'),
        'application_attribute' => env('CORE_CURRENT_APPLICATION_ATTRIBUTE', 'current_application_id'),
        'token_payload_attribute' => env('CORE_CURRENT_USER_TOKEN_PAYLOAD_ATTRIBUTE', 'auth_access_token'),
        'tenant_input_keys' => ['tenant_id'],
        'tenant_route_keys' => ['tenant_id', 'tenant'],
        'tenant_header_keys' => ['X-Tenant-Id', 'X-Tenant'],
        'application_input_keys' => ['application_id', 'app_id', 'client_id'],
        'application_header_keys' => ['X-Application-Id', 'X-App-Id', 'X-Client-Id'],
    ],
];
