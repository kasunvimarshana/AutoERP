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
        'application_attribute' => env('CORE_CURRENT_APPLICATION_ATTRIBUTE', 'current_application_id'),
    ],
    'current_tenant' => [
        'middleware_alias' => env('CORE_CURRENT_TENANT_MIDDLEWARE_ALIAS', 'current.tenant'),
        'resolver_middleware_alias' => env(
            'CORE_RESOLVE_CURRENT_TENANT_MIDDLEWARE_ALIAS',
            'resolve.current-tenant',
        ),
        'access_middleware_alias' => env(
            'CORE_REQUIRE_CURRENT_TENANT_ACCESS_MIDDLEWARE_ALIAS',
            'require.current-tenant-access',
        ),
        'required' => (bool) env('CORE_CURRENT_TENANT_REQUIRED', true),
        'request_attribute' => env('CORE_CURRENT_TENANT_REQUEST_ATTRIBUTE', 'current_tenant'),
        'id_attribute' => env('CORE_CURRENT_TENANT_ID_ATTRIBUTE', 'current_tenant_id'),
        'code_attribute' => env('CORE_CURRENT_TENANT_CODE_ATTRIBUTE', 'current_tenant_code'),
        'uuid_attribute' => env('CORE_CURRENT_TENANT_UUID_ATTRIBUTE', 'current_tenant_uuid'),
        'domain_attribute' => env('CORE_CURRENT_TENANT_DOMAIN_ATTRIBUTE', 'current_tenant_domain'),
        'status_attribute' => env('CORE_CURRENT_TENANT_STATUS_ATTRIBUTE', 'current_tenant_status'),
        'active_attribute' => env('CORE_CURRENT_TENANT_ACTIVE_ATTRIBUTE', 'current_tenant_is_active'),
        'source_attribute' => env('CORE_CURRENT_TENANT_SOURCE_ATTRIBUTE', 'current_tenant_source'),
        'application_attribute' => env('CORE_CURRENT_APPLICATION_ATTRIBUTE', 'current_application_id'),
    ],
    'current_organization_unit' => [
        'middleware_alias' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_MIDDLEWARE_ALIAS',
            'current.organization-unit',
        ),
        'required' => (bool) env('CORE_CURRENT_ORGANIZATION_UNIT_REQUIRED', false),
        'request_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_REQUEST_ATTRIBUTE',
            'current_organization_unit',
        ),
        'id_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_ID_ATTRIBUTE',
            'current_organization_unit_id',
        ),
        'tenant_id_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_TENANT_ID_ATTRIBUTE',
            'current_organization_unit_tenant_id',
        ),
        'code_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_CODE_ATTRIBUTE',
            'current_organization_unit_code',
        ),
        'path_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_PATH_ATTRIBUTE',
            'current_organization_unit_path',
        ),
        'name_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_NAME_ATTRIBUTE',
            'current_organization_unit_name',
        ),
        'active_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_ACTIVE_ATTRIBUTE',
            'current_organization_unit_is_active',
        ),
        'source_attribute' => env(
            'CORE_CURRENT_ORGANIZATION_UNIT_SOURCE_ATTRIBUTE',
            'current_organization_unit_source',
        ),
        'application_attribute' => env('CORE_CURRENT_APPLICATION_ATTRIBUTE', 'current_application_id'),
    ],
];
