<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Module Configuration
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => env('AUTH_MODULE_PER_PAGE', 15),
    ],

    'password' => [
        'min_length' => env('AUTH_PASSWORD_MIN_LENGTH', 8),
    ],

    'token' => [
        'name' => env('AUTH_TOKEN_NAME', 'auth_token'),
    ],

    'cache' => [
        'permissions_ttl' => env('AUTH_PERMISSIONS_CACHE_TTL', 300), // seconds
    ],

    'two_factor' => [
        'enabled' => env('AUTH_TWO_FACTOR_ENABLED', false),
    ],

    'statuses' => [
        'active',
        'inactive',
        'suspended',
        'pending',
    ],
];
