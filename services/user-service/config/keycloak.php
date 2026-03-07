<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Keycloak Base URL
    |--------------------------------------------------------------------------
    | The base URL of your Keycloak server, e.g. https://auth.example.com
    */
    'base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | Realm
    |--------------------------------------------------------------------------
    | The name of the Keycloak realm this service belongs to.
    */
    'realm' => env('KEYCLOAK_REALM', 'inventory'),

    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    | The client_id registered in Keycloak for this microservice.
    */
    'client_id' => env('KEYCLOAK_CLIENT_ID', 'user-service'),

    /*
    |--------------------------------------------------------------------------
    | Client Secret
    |--------------------------------------------------------------------------
    | The client_secret for confidential clients (used for Admin API calls).
    */
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Token URL
    |--------------------------------------------------------------------------
    | Derived token endpoint – used for service-to-service (client_credentials) calls.
    */
    'token_url' => env(
        'KEYCLOAK_TOKEN_URL',
        env('KEYCLOAK_BASE_URL', 'http://localhost:8080')
            . '/realms/'
            . env('KEYCLOAK_REALM', 'inventory')
            . '/protocol/openid-connect/token'
    ),

    /*
    |--------------------------------------------------------------------------
    | JWKS URL
    |--------------------------------------------------------------------------
    | Public key endpoint used to verify RS256 tokens.
    */
    'jwks_url' => env(
        'KEYCLOAK_JWKS_URL',
        env('KEYCLOAK_BASE_URL', 'http://localhost:8080')
            . '/realms/'
            . env('KEYCLOAK_REALM', 'inventory')
            . '/protocol/openid-connect/certs'
    ),

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'jwks_cache_ttl' => (int) env('KEYCLOAK_JWKS_CACHE_TTL', 3600),
];
