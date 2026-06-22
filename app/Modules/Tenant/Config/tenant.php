<?php

declare(strict_types=1);

return [
    'pagination' => ['default_per_page' => 20, 'max_per_page' => 100],
    'resolution' => [
        'central_hosts' => array_values(array_filter(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            explode(',', (string) env('TENANT_CENTRAL_HOSTS', '')),
        ))),
        'selection_headers' => [
            'id' => 'X-Tenant-Id',
            'code' => 'X-Tenant-Code',
        ],
        'local_fallback_enabled' => (bool) env('TENANT_LOCAL_FALLBACK_ENABLED', false),
        'local_fallback_domain' => env('TENANT_LOCAL_FALLBACK_DOMAIN'),
        'local_fallback_tenant_code' => env('TENANT_LOCAL_FALLBACK_TENANT_CODE', env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')),
    ],
    'documents' => [
        'disk' => env('TENANT_DOCUMENT_DISK', env('FILESYSTEM_DISK', 'local')),
        'max_size_kb' => (int) env('TENANT_DOCUMENT_MAX_SIZE_KB', 10240),
        'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],
    'domains' => [
        'verification_ttl_minutes' => (int) env('TENANT_DOMAIN_VERIFICATION_TTL_MINUTES', 1440),
        'verification_txt_prefix' => '_autoerp-verification',
        'verification_value_prefix' => 'autoerp-verification=',
    ],
];
