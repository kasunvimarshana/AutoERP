<?php

use Laravel\Passport\Passport;

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
    |
    | Passport uses encryption keys to generate secure access tokens. By
    | default, the keys are stored as local files but can be loaded from
    | environment variables for 12-factor / containerised deployments.
    |
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
    | Token Lifetimes
    |--------------------------------------------------------------------------
    */

    'token_expire_days'         => env('PASSPORT_TOKEN_EXPIRE_DAYS', 7),
    'refresh_token_expire_days' => env('PASSPORT_REFRESH_TOKEN_EXPIRE_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Passport Scopes
    |--------------------------------------------------------------------------
    |
    | '*'    – wildcard; only granted to admin-role tokens
    | read   – GET requests to any proxied service
    | write  – POST / PUT / PATCH to any proxied service
    | delete – DELETE to any proxied service
    |
    */

    'scopes' => [
        '*'      => 'Full access (admin only)',
        'read'   => 'Read any resource',
        'write'  => 'Create and update resources',
        'delete' => 'Delete resources',
    ],

];
