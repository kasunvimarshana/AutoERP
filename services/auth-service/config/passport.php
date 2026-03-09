<?php

use Carbon\CarbonInterval;

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),
    'public_key'  => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    */

    'client_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    */

    'personal_access_client' => [
        'id'     => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage Driver
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'pgsql'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    */

    'tokens_expire_in'         => CarbonInterval::days(env('PASSPORT_TOKEN_EXPIRE_DAYS', 15)),
    'refresh_tokens_expire_in' => CarbonInterval::days(env('PASSPORT_REFRESH_TOKEN_EXPIRE_DAYS', 30)),
    'personal_access_tokens_expire_in' => CarbonInterval::months(
        env('PASSPORT_PERSONAL_ACCESS_TOKEN_EXPIRE_MONTHS', 6)
    ),

    /*
    |--------------------------------------------------------------------------
    | Token Hashing
    |--------------------------------------------------------------------------
    */

    'hash_client_secrets' => false,

];
