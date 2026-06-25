<?php

declare(strict_types=1);

return [
    'guard' => env('USER_MODULE_GUARD', (string) config('auth.defaults.guard', 'api')),
    'platform' => [
        'permission_middleware_alias' => env('PLATFORM_PERMISSION_MIDDLEWARE_ALIAS', 'platform.permission'),
        'operator_invitation_url' => env('PLATFORM_OPERATOR_INVITATION_URL', rtrim((string) env('PLATFORM_PUBLIC_URL', env('APP_URL')), '/').'/register/platform-operator'),
        'operator_invitation_ttl_minutes' => (int) env('PLATFORM_OPERATOR_INVITATION_TTL_MINUTES', 1440),
        'operator_invitation_delivery_lease_seconds' => (int) env('PLATFORM_OPERATOR_INVITATION_DELIVERY_LEASE_SECONDS', 300),
    ],
    'context' => [
        'middleware_alias' => env('USER_CONTEXT_MIDDLEWARE_ALIAS', 'current.user-record'),
        'request_attribute' => env('USER_CONTEXT_REQUEST_ATTRIBUTE', 'current_user_record'),
        'id_attribute' => env('USER_CONTEXT_ID_ATTRIBUTE', 'current_user_record_id'),
        'tenant_id_attribute' => env('USER_CONTEXT_TENANT_ID_ATTRIBUTE', 'current_user_record_tenant_id'),
    ],
];
