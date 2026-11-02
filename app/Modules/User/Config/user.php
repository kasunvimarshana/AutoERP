<?php

declare(strict_types=1);

return [
    'guard' => env('USER_MODULE_GUARD', (string) config('auth.defaults.guard', 'api')),
    'platform' => [
        'permission_middleware_alias' => env('PLATFORM_PERMISSION_MIDDLEWARE_ALIAS', 'platform.permission'),
    ],
    'storage' => [
        'documents' => [
            'max_size_kb' => (int) env('USER_DOCUMENT_MAX_SIZE_KB', 10240),
            'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
        ],
    ],
    'context' => [
        'middleware_alias' => env('USER_CONTEXT_MIDDLEWARE_ALIAS', 'current.user-record'),
        'request_attribute' => env('USER_CONTEXT_REQUEST_ATTRIBUTE', 'current_user_record'),
        'id_attribute' => env('USER_CONTEXT_ID_ATTRIBUTE', 'current_user_record_id'),
        'tenant_id_attribute' => env('USER_CONTEXT_TENANT_ID_ATTRIBUTE', 'current_user_record_tenant_id'),
    ],
];
