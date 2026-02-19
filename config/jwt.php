<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all JWT (JSON Web Token) authentication settings
    | for stateless, multi-device, multi-organization authentication.
    |
    */

    'enabled' => env('JWT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign JWT tokens. MUST be set in .env file.
    | Use a strong, random string for production environments.
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign tokens. Supported: HS256, HS384, HS512
    |
    */

    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Token Time-To-Live (TTL)
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) an access token is valid before it expires.
    | Default: 3600 seconds (1 hour)
    |
    */

    'ttl' => env('JWT_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) a refresh token is valid. Refresh tokens are
    | used to obtain new access tokens without re-authenticating.
    | Default: 86400 seconds (24 hours)
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Multi-Device Support
    |--------------------------------------------------------------------------
    |
    | Enable authentication per user × device × organization.
    | Each device gets its own token for concurrent access.
    |
    */

    'multi_device' => [
        'enabled' => env('JWT_MULTI_DEVICE_ENABLED', true),
        'max_devices_per_user' => env('JWT_MAX_DEVICES_PER_USER', 5),
        'track_device_info' => env('JWT_TRACK_DEVICE_INFO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Claims
    |--------------------------------------------------------------------------
    |
    | Custom claims to include in every JWT token.
    |
    */

    'claims' => [
        'issuer' => env('JWT_ISSUER', config('app.name')),
        'audience' => env('JWT_AUDIENCE', config('app.url')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Revocation
    |--------------------------------------------------------------------------
    |
    | Configuration for token revocation list (blacklist).
    | When a user logs out or token is compromised, it's added to this list.
    |
    */

    'revocation' => [
        'enabled' => env('JWT_REVOCATION_ENABLED', true),
        'cache_enabled' => env('JWT_REVOCATION_CACHE', true),
        'cache_ttl' => env('JWT_REVOCATION_CACHE_TTL', 3600),
        'cleanup_enabled' => env('JWT_REVOCATION_CLEANUP', true),
        'cleanup_schedule' => env('JWT_REVOCATION_CLEANUP_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Validation
    |--------------------------------------------------------------------------
    |
    | Strict validation rules for incoming JWT tokens.
    |
    */

    'validation' => [
        'verify_signature' => true,
        'verify_expiration' => true,
        'verify_not_before' => env('JWT_VERIFY_NBF', true),
        'verify_issuer' => env('JWT_VERIFY_ISSUER', true),
        'verify_audience' => env('JWT_VERIFY_AUDIENCE', false),
        'leeway' => env('JWT_LEEWAY', 0), // Seconds of leeway for clock skew
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Additional security measures for JWT authentication.
    |
    */

    'security' => [
        'require_https' => env('JWT_REQUIRE_HTTPS', env('APP_ENV') === 'production'),
        'ip_validation' => env('JWT_IP_VALIDATION', false),
        'user_agent_validation' => env('JWT_UA_VALIDATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for authentication events.
    |
    */

    'events' => [
        'enabled' => env('JWT_EVENTS_ENABLED', true),
    ],
];
