<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which cross-origin requests may be accepted. Values are read
    | from environment variables so each deployment can set appropriate
    | origins without code changes.
    |
    */

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (function () {
        $origins = array_filter(
            explode(',', env('CORS_ALLOWED_ORIGINS', '*'))
        );

        return empty($origins) ? ['*'] : $origins;
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),
];
