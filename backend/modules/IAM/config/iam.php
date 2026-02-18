<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IAM Module Configuration
    |--------------------------------------------------------------------------
    */

    'login_attempts' => [
        'max_attempts' => env('IAM_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('IAM_LOCKOUT_DURATION', 15), // minutes
    ],

    'password' => [
        'min_length' => env('IAM_PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('IAM_PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('IAM_PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('IAM_PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('IAM_PASSWORD_REQUIRE_SYMBOLS', true),
    ],

    'token' => [
        'expiration' => env('IAM_TOKEN_EXPIRATION', 1440), // minutes (1 day)
        'refresh_expiration' => env('IAM_TOKEN_REFRESH_EXPIRATION', 43200), // minutes (30 days)
    ],

    'mfa' => [
        'enabled' => env('IAM_MFA_ENABLED', false),
        'required_for_admin' => env('IAM_MFA_REQUIRED_FOR_ADMIN', false),
    ],

    'email_verification' => [
        'required' => env('IAM_EMAIL_VERIFICATION_REQUIRED', false),
    ],
];
