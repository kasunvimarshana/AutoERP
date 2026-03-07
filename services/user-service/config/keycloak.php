<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Keycloak Server Configuration
    |--------------------------------------------------------------------------
    */
    'base_url'      => env('KEYCLOAK_BASE_URL', 'http://keycloak:8080'),
    'realm'         => env('KEYCLOAK_REALM', 'saas-realm'),
    'client_id'     => env('KEYCLOAK_CLIENT_ID', 'user-service'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Public Key / JWKS
    |--------------------------------------------------------------------------
    | The RSA public key used to verify Keycloak-issued JWTs.
    | Can be set directly (PEM) or fetched from the JWKS endpoint at runtime.
    */
    'public_key'   => env('KEYCLOAK_PUBLIC_KEY', ''),
    'jwks_uri'     => env(
        'KEYCLOAK_JWKS_URI',
        env('KEYCLOAK_BASE_URL', 'http://keycloak:8080')
            .'/realms/'.env('KEYCLOAK_REALM', 'saas-realm')
            .'/protocol/openid-connect/certs'
    ),

    /*
    |--------------------------------------------------------------------------
    | Token Claims Mapping
    |--------------------------------------------------------------------------
    | Keys inside the JWT payload that map to internal concepts.
    */
    'claims' => [
        'tenant_id' => env('KEYCLOAK_CLAIM_TENANT_ID', 'tenant_id'),
        'user_id'   => env('KEYCLOAK_CLAIM_USER_ID', 'sub'),
        'roles'     => env('KEYCLOAK_CLAIM_ROLES', 'realm_access.roles'),
        'email'     => env('KEYCLOAK_CLAIM_EMAIL', 'email'),
        'username'  => env('KEYCLOAK_CLAIM_USERNAME', 'preferred_username'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Algorithms
    |--------------------------------------------------------------------------
    */
    'algorithms' => ['RS256'],

    /*
    |--------------------------------------------------------------------------
    | Token Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'token_cache_ttl' => env('KEYCLOAK_TOKEN_CACHE_TTL', 300),
];
