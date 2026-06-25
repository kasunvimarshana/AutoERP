<?php

declare(strict_types=1);

use Modules\Auth\Services\InternalAuthenticationProvider;

$platformMfaRequired = filter_var(
    env('AUTH_PLATFORM_MFA_REQUIRED', env('APP_ENV') === 'production'),
    FILTER_VALIDATE_BOOL,
);
$platformMfaEnabled = filter_var(
    env('AUTH_PLATFORM_MFA_ENABLED', $platformMfaRequired),
    FILTER_VALIDATE_BOOL,
);

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
    'cookies' => [
        'tenant_refresh' => [
            'name' => env('AUTH_TENANT_REFRESH_COOKIE_NAME', 'autoerp_tenant_refresh'),
            'path' => env('AUTH_TENANT_REFRESH_COOKIE_PATH', '/api/v1/auth'),
            'domain' => env('AUTH_TENANT_REFRESH_COOKIE_DOMAIN'),
            'secure' => filter_var(
                env('AUTH_TENANT_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'),
                FILTER_VALIDATE_BOOL,
            ),
            'same_site' => env('AUTH_TENANT_REFRESH_COOKIE_SAME_SITE', 'strict'),
        ],
        'platform_refresh' => [
            'name' => env('AUTH_PLATFORM_REFRESH_COOKIE_NAME', 'autoerp_platform_refresh'),
            'path' => env('AUTH_PLATFORM_REFRESH_COOKIE_PATH', '/api/v1/platform/auth'),
            'domain' => env('AUTH_PLATFORM_REFRESH_COOKIE_DOMAIN'),
            'secure' => filter_var(
                env('AUTH_PLATFORM_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'),
                FILTER_VALIDATE_BOOL,
            ),
            'same_site' => env('AUTH_PLATFORM_REFRESH_COOKIE_SAME_SITE', 'strict'),
        ],
    ],
    'authorization_code_ttl_seconds' => (int) env('AUTH_AUTHORIZATION_CODE_TTL', 300),
    'verification_ttl_seconds' => (int) env('AUTH_VERIFICATION_TTL', 600),
    'registration' => [
        'invitation_expiry_hours' => (int) env('AUTH_REGISTRATION_INVITATION_EXPIRY_HOURS', 72),
        'delivery_lease_seconds' => (int) env('AUTH_REGISTRATION_DELIVERY_LEASE_SECONDS', 300),
        'delivery_stale_after_seconds' => (int) env('AUTH_REGISTRATION_DELIVERY_STALE_AFTER_SECONDS', 900),
        'invitation_url' => env(
            'AUTH_REGISTRATION_INVITATION_URL',
            rtrim((string) env('PLATFORM_PUBLIC_URL', ''), '/').'/register/invitation',
        ),
    ],
    'platform_mfa' => [
        // MFA capability and the policies that use it are intentionally separate.
        // Disabling MFA stops login challenges and MFA step-up checks, even when an
        // operator still has an enrolled method. Enrollment data is retained so it
        // can be re-enabled safely without recreating secrets.
        'enabled' => $platformMfaEnabled,
        'required' => $platformMfaEnabled && $platformMfaRequired,
        'login_challenge' => $platformMfaEnabled && filter_var(
            env('AUTH_PLATFORM_MFA_LOGIN_CHALLENGE', true),
            FILTER_VALIDATE_BOOL,
        ),
        'step_up_required' => $platformMfaEnabled && filter_var(
            env('AUTH_PLATFORM_MFA_STEP_UP_REQUIRED', $platformMfaRequired),
            FILTER_VALIDATE_BOOL,
        ),
        'issuer' => env('AUTH_PLATFORM_MFA_ISSUER', env('APP_NAME', 'AutoERP')),
        'step_up_ttl_seconds' => (int) env('AUTH_PLATFORM_STEP_UP_TTL_SECONDS', 900),
        'middleware_alias' => env('AUTH_PLATFORM_STEP_UP_MIDDLEWARE_ALIAS', 'platform.step-up'),
    ],
    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
    'login_attempt_window_seconds' => (int) env('AUTH_LOGIN_ATTEMPT_WINDOW', 900),
    'middleware' => [
        'authenticate_alias' => env('AUTH_MIDDLEWARE_AUTHENTICATE_ALIAS', 'auth.module.authenticate'),
        'token_validation_alias' => env('AUTH_MIDDLEWARE_TOKEN_VALIDATION_ALIAS', 'auth.module.token'),
        'context_alias' => env('AUTH_MIDDLEWARE_CONTEXT_ALIAS', 'auth.module.context'),
        'sso_context_alias' => env('AUTH_MIDDLEWARE_SSO_CONTEXT_ALIAS', 'auth.module.sso-context'),
    ],
];
