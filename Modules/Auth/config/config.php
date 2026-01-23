<?php

return [
    'name' => 'Auth',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for authentication endpoints to prevent
    | brute force attacks and abuse.
    |
    */

    'rate_limits' => [
        'login' => [
            'max_attempts' => env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('AUTH_LOGIN_DECAY_MINUTES', 1),
        ],

        'register' => [
            'max_attempts' => env('AUTH_REGISTER_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('AUTH_REGISTER_DECAY_MINUTES', 1),
        ],

        'password_reset' => [
            'max_attempts' => env('AUTH_PASSWORD_RESET_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('AUTH_PASSWORD_RESET_DECAY_MINUTES', 1),
        ],

        'email_verification' => [
            'max_attempts' => env('AUTH_EMAIL_VERIFICATION_MAX_ATTEMPTS', 10),
            'decay_minutes' => env('AUTH_EMAIL_VERIFICATION_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configure authentication token settings.
    |
    */

    'token' => [
        'name' => env('AUTH_TOKEN_NAME', 'auth-token'),
        'expires_in' => env('AUTH_TOKEN_EXPIRES_IN', null), // null = never expires
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    |
    | Configure email verification settings.
    |
    */

    'email_verification' => [
        'enabled' => env('AUTH_EMAIL_VERIFICATION_ENABLED', true),
        'expires_in' => env('AUTH_EMAIL_VERIFICATION_EXPIRES_IN', 60), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    |
    | Configure password reset settings.
    |
    */

    'password_reset' => [
        'expires_in' => env('AUTH_PASSWORD_RESET_EXPIRES_IN', 60), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The default role to assign to newly registered users.
    |
    */

    'default_role' => env('AUTH_DEFAULT_ROLE', 'user'),
];
