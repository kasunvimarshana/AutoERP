<?php

use Laravel\Passport\Passport;

return [
    'guard' => null,
    'tokens_expire_in' => Passport::tokensExpireIn(now()->addDays(15)),
    'refresh_tokens_expire_in' => Passport::refreshTokensExpireIn(now()->addDays(30)),
    'personal_access_tokens_expire_in' => Passport::personalAccessTokensExpireIn(now()->addMonths(6)),
    'token_introspection_endpoint' => env('TOKEN_INTROSPECTION_ENDPOINT', null),
    'private_key' => env('PASSPORT_PRIVATE_KEY', null),
    'public_key' => env('PASSPORT_PUBLIC_KEY', null),
    'unserializes_cookies' => false,
    'encrypt_cookies' => true,
    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],
];
