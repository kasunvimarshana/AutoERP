<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | SECURITY WARNING: JWT_SECRET must be set explicitly in production.
    | Never use APP_KEY for JWT signing in production environments.
    | Generate a strong secret: php -r "echo base64_encode(random_bytes(32));"
    |
    | Note: Validation happens via config:validate command to avoid breaking
    | composer install and package discovery during deployment.
    |
    */

    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Token TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Token lifetime in seconds
    |
    */

    'ttl' => env('JWT_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Refresh token lifetime in seconds
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 86400), // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Algorithm
    |--------------------------------------------------------------------------
    |
    | Signing algorithm for JWT
    |
    */

    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Multi-Device Support
    |--------------------------------------------------------------------------
    */

    'multi_device' => [
        'enabled' => env('JWT_MULTI_DEVICE_ENABLED', true),
        'max_devices_per_user' => env('JWT_MAX_DEVICES_PER_USER', 5),
    ],
];
