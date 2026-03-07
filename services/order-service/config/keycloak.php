<?php

return [
    'base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080'),
    'realm' => env('KEYCLOAK_REALM', 'inventory-management'),
    'client_id' => env('KEYCLOAK_CLIENT_ID', 'inventory-api'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET', ''),
    'public_key_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080').'/realms/'.env('KEYCLOAK_REALM', 'inventory-management').'/protocol/openid-connect/certs',
];
