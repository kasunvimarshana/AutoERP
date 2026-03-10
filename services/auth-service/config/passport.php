<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Passport Configuration
    |--------------------------------------------------------------------------
    */

    // Personal access token lifetime (days)
    'personal_access_token_expire_in' => (int) env('PASSPORT_TOKEN_EXPIRE_DAYS', 15),

    // Password grant token lifetime (days)
    'password_grant_token_expire_in' => (int) env('PASSPORT_PASSWORD_GRANT_EXPIRE_DAYS', 15),

    // Refresh token lifetime (days)
    'refresh_token_expire_in' => (int) env('PASSPORT_REFRESH_TOKEN_EXPIRE_DAYS', 30),

    // Guards that Passport should be available for
    'guard' => env('PASSPORT_GUARD', 'api'),

    // Client credentials grant (for service-to-service)
    'client_credentials_grant_enabled' => (bool) env('PASSPORT_CLIENT_CREDENTIALS', true),
];
