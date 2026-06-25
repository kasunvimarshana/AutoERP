<?php

declare(strict_types=1);

return [
    'guard' => env('USER_MODULE_GUARD', (string) config('auth.defaults.guard', 'api')),
    'context' => [
        'middleware_alias' => env('USER_CONTEXT_MIDDLEWARE_ALIAS', 'current.user-record'),
        'request_attribute' => env('USER_CONTEXT_REQUEST_ATTRIBUTE', 'current_user_record'),
        'id_attribute' => env('USER_CONTEXT_ID_ATTRIBUTE', 'current_user_record_id'),
        'tenant_id_attribute' => env('USER_CONTEXT_TENANT_ID_ATTRIBUTE', 'current_user_record_tenant_id'),
        'organization_unit_id_attribute' => env(
            'USER_CONTEXT_ORGANIZATION_UNIT_ID_ATTRIBUTE',
            'current_user_record_organization_unit_id',
        ),
    ],
];
