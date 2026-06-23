<?php

declare(strict_types=1);
use Modules\Auth\Services\InternalAuthenticationProvider;

return [
    'default_provider_key' => env('AUTH_DEFAULT_PROVIDER_KEY', 'internal'),
    'protected_route_guard' => env('MODULE_AUTH_PROTECTED_GUARD', 'auth-api'),
    'platform_protected_route_guard' => env('MODULE_PLATFORM_AUTH_PROTECTED_GUARD', 'platform-api'),
    'token_guard_driver' => env('MODULE_AUTH_TOKEN_GUARD_DRIVER', 'module-auth-token'),
    'platform_token_guard_driver' => env('MODULE_PLATFORM_AUTH_TOKEN_GUARD_DRIVER', 'module-platform-token'),
    'provider_drivers' => [
        'internal' => InternalAuthenticationProvider::class,
    ],
    'current_user_context' => [
        'token_payload_attribute' => env('AUTH_TOKEN_PAYLOAD_ATTRIBUTE', 'auth_access_token'),
    ],
    'access_token_ttl_seconds' => (int) env('AUTH_ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl_seconds' => (int) env('AUTH_REFRESH_TOKEN_TTL', 2592000),
    'web_refresh_cookie' => [
        'name' => env('AUTH_REFRESH_COOKIE_NAME', 'autoerp_refresh_token'),
        'path' => env('AUTH_REFRESH_COOKIE_PATH', '/api'),
        'domain' => env('AUTH_REFRESH_COOKIE_DOMAIN'),
        'secure' => (bool) env('AUTH_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'),
        'same_site' => env('AUTH_REFRESH_COOKIE_SAME_SITE', 'strict'),
    ],
    'authorization_code_ttl_seconds' => (int) env('AUTH_AUTHORIZATION_CODE_TTL', 300),
    'verification_ttl_seconds' => (int) env('AUTH_VERIFICATION_TTL', 600),
    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
    'login_attempt_window_seconds' => (int) env('AUTH_LOGIN_ATTEMPT_WINDOW', 900),
    'middleware' => [
        'authenticate_alias' => env('AUTH_MIDDLEWARE_AUTHENTICATE_ALIAS', 'auth.module.authenticate'),
        'token_validation_alias' => env('AUTH_MIDDLEWARE_TOKEN_VALIDATION_ALIAS', 'auth.module.token'),
        'context_alias' => env('AUTH_MIDDLEWARE_CONTEXT_ALIAS', 'auth.module.context'),
        'sso_context_alias' => env('AUTH_MIDDLEWARE_SSO_CONTEXT_ALIAS', 'auth.module.sso-context'),
    ],
];
