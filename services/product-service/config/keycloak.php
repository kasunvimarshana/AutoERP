<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Keycloak Configuration
    |--------------------------------------------------------------------------
    */
    'base_url'    => env('KEYCLOAK_BASE_URL', 'http://keycloak:8080'),
    'realm'       => env('KEYCLOAK_REALM', 'saas-realm'),
    'client_id'   => env('KEYCLOAK_CLIENT_ID', 'product-service'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    'jwks_uri'    => env(
        'KEYCLOAK_JWKS_URI',
        env('KEYCLOAK_BASE_URL', 'http://keycloak:8080')
        .'/realms/'.env('KEYCLOAK_REALM', 'saas-realm')
        .'/protocol/openid-connect/certs'
    ),
    'token_url'   => env('KEYCLOAK_BASE_URL', 'http://keycloak:8080')
        .'/realms/'.env('KEYCLOAK_REALM', 'saas-realm')
        .'/protocol/openid-connect/token',

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    | Used for machine-to-machine calls (e.g. calling inventory-service)
    |--------------------------------------------------------------------------
    */
    'service_account' => [
        'client_id'     => env('SERVICE_ACCOUNT_CLIENT_ID', 'product-service-internal'),
        'client_secret' => env('SERVICE_ACCOUNT_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'jwks_cache_ttl' => env('KEYCLOAK_JWKS_CACHE_TTL', 3600),
];
