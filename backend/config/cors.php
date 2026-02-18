<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: For production, set CORS_ALLOWED_ORIGINS environment variable
    | to a comma-separated list of allowed domains.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 
        'http://localhost:5173,http://localhost:3000'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Tenant-ID',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'Accept',
    ],

    'exposed_headers' => ['X-Tenant-ID'],

    'max_age' => 3600,

    'supports_credentials' => true,

];
