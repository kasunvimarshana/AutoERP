<?php

declare(strict_types=1);

return [
    'internal_provider_key' => 'internal',
    'protected_route_guard' => env('MODULE_AUTH_PROTECTED_GUARD', 'auth-api'),
    'platform_protected_route_guard' => env('MODULE_PLATFORM_AUTH_PROTECTED_GUARD', 'platform-api'),
    'token_guard_driver' => env('MODULE_AUTH_TOKEN_GUARD_DRIVER', 'module-auth-token'),
    'platform_token_guard_driver' => env('MODULE_PLATFORM_AUTH_TOKEN_GUARD_DRIVER', 'module-platform-token'),
    'current_user_context' => [
        'token_payload_attribute' => 'auth_access_token',
    ],
    'tokens' => [
        'access_ttl_seconds' => (int) env('AUTH_ACCESS_TOKEN_TTL', 3600),
        'refresh_ttl_seconds' => (int) env('AUTH_REFRESH_TOKEN_TTL', 2592000),
    ],
    'sessions' => [
        'tenant_ttl_seconds' => (int) env('AUTH_TENANT_SESSION_TTL', 2592000),
        'platform_ttl_seconds' => (int) env('AUTH_PLATFORM_SESSION_TTL', 86400),
        'activity_touch_interval_seconds' => (int) env('AUTH_ACTIVITY_TOUCH_INTERVAL', 300),
    ],
    'oauth' => [
        'authorization_code_ttl_seconds' => (int) env('AUTH_AUTHORIZATION_CODE_TTL', 300),
        'scopes' => ['tenant'],
    ],
    'rate_limits' => [
        'account_max_attempts' => (int) env('AUTH_ACCOUNT_MAX_ATTEMPTS', 5),
        'account_ip_max_attempts' => (int) env('AUTH_ACCOUNT_IP_MAX_ATTEMPTS', 8),
        'global_ip_max_attempts' => (int) env('AUTH_GLOBAL_IP_MAX_ATTEMPTS', 30),
        'window_seconds' => (int) env('AUTH_LOGIN_ATTEMPT_WINDOW', 900),
        'refresh_per_minute' => (int) env('AUTH_REFRESH_PER_MINUTE', 30),
        'oauth_exchange_per_minute' => (int) env('AUTH_OAUTH_EXCHANGE_PER_MINUTE', 30),
        'invitations_per_minute' => (int) env('AUTH_INVITATIONS_PER_MINUTE', 10),
        'tenant_login' => env('AUTH_TENANT_LOGIN_LIMITER', 'auth.tenant.login'),
        'tenant_refresh' => env('AUTH_TENANT_REFRESH_LIMITER', 'auth.tenant.refresh'),
        'oauth_exchange' => env('AUTH_OAUTH_EXCHANGE_LIMITER', 'auth.oauth.exchange'),
        'platform_login' => env('AUTH_PLATFORM_LOGIN_LIMITER', 'auth.platform.login'),
        'platform_refresh' => env('AUTH_PLATFORM_REFRESH_LIMITER', 'auth.platform.refresh'),
        'invitations' => env('AUTH_INVITATION_LIMITER', 'auth.invitations'),
    ],
    'password' => [
        'minimum_length' => (int) env('AUTH_PASSWORD_MINIMUM_LENGTH', 12),
    ],
    'cookies' => [
        'tenant_refresh' => [
            'name' => env('AUTH_TENANT_REFRESH_COOKIE_NAME', 'autoerp_tenant_refresh'),
            'path' => env('AUTH_TENANT_REFRESH_COOKIE_PATH', '/api/v1/auth'),
            'domain' => env('AUTH_TENANT_REFRESH_COOKIE_DOMAIN'),
            'secure' => filter_var(env('AUTH_TENANT_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
            'same_site' => env('AUTH_TENANT_REFRESH_COOKIE_SAME_SITE', 'strict'),
        ],
        'platform_refresh' => [
            'name' => env('AUTH_PLATFORM_REFRESH_COOKIE_NAME', 'autoerp_platform_refresh'),
            'path' => env('AUTH_PLATFORM_REFRESH_COOKIE_PATH', '/api/v1/platform/auth'),
            'domain' => env('AUTH_PLATFORM_REFRESH_COOKIE_DOMAIN'),
            'secure' => filter_var(env('AUTH_PLATFORM_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
            'same_site' => env('AUTH_PLATFORM_REFRESH_COOKIE_SAME_SITE', 'strict'),
        ],
    ],
    'registration' => [
        'invitation_expiry_hours' => (int) env('AUTH_REGISTRATION_INVITATION_EXPIRY_HOURS', 72),
        'delivery_lease_seconds' => (int) env('AUTH_REGISTRATION_DELIVERY_LEASE_SECONDS', 300),
        'delivery_stale_after_seconds' => (int) env('AUTH_REGISTRATION_DELIVERY_STALE_AFTER_SECONDS', 900),
        'invitation_url' => env(
            'AUTH_REGISTRATION_INVITATION_URL',
            rtrim((string) env('PLATFORM_PUBLIC_URL', ''), '/').'/register/invitation',
        ),
    ],
    'platform_step_up' => [
        'ttl_seconds' => (int) env('AUTH_PLATFORM_STEP_UP_TTL_SECONDS', 900),
        'middleware_alias' => 'platform.step-up',
    ],
    'retention' => [
        'authorization_codes_days' => (int) env('AUTH_RETENTION_AUTHORIZATION_CODES_DAYS', 7),
        'login_attempts_days' => (int) env('AUTH_RETENTION_LOGIN_ATTEMPTS_DAYS', 90),
        'terminal_tokens_days' => (int) env('AUTH_RETENTION_TERMINAL_TOKENS_DAYS', 30),
        'terminal_sessions_days' => (int) env('AUTH_RETENTION_TERMINAL_SESSIONS_DAYS', 90),
        'processed_events_days' => (int) env('AUTH_RETENTION_PROCESSED_EVENTS_DAYS', 30),
        'invitation_deliveries_days' => (int) env('AUTH_RETENTION_INVITATION_DELIVERIES_DAYS', 90),
    ],
];
