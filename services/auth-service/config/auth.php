<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ],

        'api-jwt' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],

        // Dynamically registered per-tenant guards follow the pattern:
        // 'tenant_{tenant_id}' => ['driver' => 'passport', 'provider' => 'tenant_users_{tenant_id}']
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Domain\Models\User::class,
        ],

        // Dynamically registered per-tenant providers follow the pattern:
        // 'tenant_users_{tenant_id}' => ['driver' => 'eloquent', 'model' => App\Domain\Models\User::class]
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('PASSWORD_RESET_TIMEOUT', 10800),

];
