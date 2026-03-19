<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SSO Runtime Configuration
    |--------------------------------------------------------------------------
    |
    | These values drive the distributed authentication and authorization
    | system. All values can be overridden per-tenant at runtime via the
    | TenantConfig system without redeployment.
    |
    */

    'token' => [
        'access_ttl_minutes'         => (int) env('SSO_ACCESS_TOKEN_TTL_MINUTES', 15),
        'refresh_ttl_days'           => (int) env('SSO_REFRESH_TOKEN_TTL_DAYS', 30),
        'personal_access_ttl_months' => (int) env('SSO_PERSONAL_ACCESS_TOKEN_TTL_MONTHS', 6),
        'service_token_ttl_minutes'  => (int) env('SSO_SERVICE_TOKEN_TTL_MINUTES', 60),
    ],

    'revocation' => [
        'default_ttl_seconds' => (int) env('SSO_REVOCATION_TTL_SECONDS', 900),
        'user_revocation_ttl_seconds' => (int) env('SSO_USER_REVOCATION_TTL_SECONDS', 86400),
    ],

    'security' => [
        'max_failed_attempts'  => (int) env('SSO_MAX_FAILED_ATTEMPTS', 10),
        'lockout_seconds'      => (int) env('SSO_LOCKOUT_SECONDS', 900),
        'require_2fa'          => (bool) env('SSO_REQUIRE_2FA', false),
        'session_binding'      => (bool) env('SSO_SESSION_BINDING', true),
    ],

    'rate_limiting' => [
        'auth_per_minute'  => (int) env('SSO_RATE_AUTH_PER_MINUTE', 10),
        'api_per_minute'   => (int) env('SSO_RATE_API_PER_MINUTE', 60),
        'service_per_minute' => (int) env('SSO_RATE_SERVICE_PER_MINUTE', 600),
    ],

    'service_auth' => [
        'enabled'       => (bool) env('SSO_SERVICE_AUTH_ENABLED', true),
        'issuer'        => env('SSO_SERVICE_ISSUER', env('APP_URL', 'http://localhost')),
        'audience'      => env('SSO_SERVICE_AUDIENCE', 'kv-sso-microservices'),
    ],

    'public_key' => [
        'cache_ttl_seconds' => (int) env('SSO_PUBLIC_KEY_CACHE_TTL', 3600),
    ],

    'tenant' => [
        'default_locale'        => env('SSO_DEFAULT_LOCALE', 'en'),
        'default_timezone'      => env('SSO_DEFAULT_TIMEZONE', 'UTC'),
        'default_currency'      => env('SSO_DEFAULT_CURRENCY', 'USD'),
        'allow_self_register'   => (bool) env('SSO_TENANT_ALLOW_SELF_REGISTER', false),
    ],

    'feature_flags' => [
        'cache_ttl_seconds' => (int) env('SSO_FEATURE_FLAG_CACHE_TTL', 300),
    ],

];
