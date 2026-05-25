<?php

declare(strict_types=1);

return [
    'default_provider_key' => env('AUTH_DEFAULT_PROVIDER_KEY', 'internal'),
    'protected_route_guard' => env('MODULE_AUTH_PROTECTED_GUARD', 'auth-api'),
    'token_guard_driver' => env('MODULE_AUTH_TOKEN_GUARD_DRIVER', 'module-auth-token'),
    'token_guard_tenant_input_key' => env('MODULE_AUTH_TOKEN_TENANT_INPUT_KEY', 'tenant_id'),
    'provider_drivers' => [
        'internal' => Modules\Auth\Infrastructure\Services\InternalAuthenticationProvider::class,
    ],
    'access_token_ttl_seconds' => (int) env('AUTH_ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl_seconds' => (int) env('AUTH_REFRESH_TOKEN_TTL', 2592000),
    'authorization_code_ttl_seconds' => (int) env('AUTH_AUTHORIZATION_CODE_TTL', 300),
    'verification_ttl_seconds' => (int) env('AUTH_VERIFICATION_TTL', 600),
    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
    'login_attempt_window_seconds' => (int) env('AUTH_LOGIN_ATTEMPT_WINDOW', 900),
];
