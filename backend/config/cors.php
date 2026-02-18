<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
<<<<<<< HEAD
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
=======
    | IMPORTANT: For production, set CORS_ALLOWED_ORIGINS environment variable
    | to a comma-separated list of allowed domains.
>>>>>>> kv-erp-001
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

<<<<<<< HEAD
    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,
=======
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
>>>>>>> kv-erp-001

    'supports_credentials' => true,

];
