<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    */
    'guard' => env('PASSPORT_GUARD', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    */
    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    | By default, Passport uses auto-incrementing primary keys when assigning
    | IDs to clients. You may instruct Passport to use UUIDs instead using
    | the setting below.
    */
    'client_uuids' => env('PASSPORT_CLIENT_UUIDS', false),

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    | If you enable client UUIDs, the personal access client ID and secret
    | can be set via environment variables rather than database lookups.
    */
    'personal_access_client' => [
        'id'     => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage Driver
    |--------------------------------------------------------------------------
    | This configuration value allows you to customize the storage driver
    | used by Passport to store client and token records. Supported:
    | "eloquent" and "database".
    */
    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    | Here you may define token expiration intervals. Access tokens expire
    | after the given number of days/minutes. Set to null for non-expiring.
    */
    'token_expiration' => env('PASSPORT_TOKEN_EXPIRATION', 15),

    'refresh_token_expiration' => env('PASSPORT_REFRESH_TOKEN_EXPIRATION', 30),

];
