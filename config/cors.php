<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    | Common fix for "Cross-Origin Request Blocked" errors:
    |   Set CORS_ALLOWED_ORIGINS in your .env file to allow specific origins,
    |   or set it to "*" to allow all origins (development only).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | The paths that CORS headers should be applied to. The api/* wildcard
    | covers all REST API endpoints; the docs/* and api/documentation paths
    | ensure the Swagger/OpenAPI UI also sends proper CORS headers so it can
    | be embedded in or called from external front-ends.
    |
    */
    'paths' => ['api/*', 'docs', 'docs/*', 'api/documentation', 'oauth/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_METHODS to a comma-separated list (e.g. "GET,POST")
    | or leave the default "*" to allow all HTTP verbs.
    |
    */
    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', '*')),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_ORIGINS to a comma-separated list of allowed origins
    | (e.g. "https://app.example.com,https://admin.example.com").
    | Use "*" to allow all origins (NOT recommended in production).
    |
    */
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Origins matched against these regex patterns are also allowed. Leave
    | empty unless you need wildcard sub-domain matching.
    |
    */
    'allowed_origins_patterns' => array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_HEADERS to a comma-separated list of headers
    | (e.g. "Content-Type,Authorization") or "*" to allow all.
    |
    */
    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', '*')),

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers that the browser is allowed to access from the response.
    | Leave empty unless your front-end needs specific headers exposed.
    |
    */
    'exposed_headers' => array_filter(
        explode(',', env('CORS_EXPOSED_HEADERS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | The number of seconds the browser may cache preflight request results.
    | 0 disables caching (useful during development). Set CORS_MAX_AGE in
    | your .env file to raise this in production.
    |
    */
    'max_age' => (int) env('CORS_MAX_AGE', 0),

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Set CORS_SUPPORTS_CREDENTIALS=true in your .env file when the browser
    | needs to include cookies or HTTP authentication with cross-origin
    | requests (e.g. when using Passport with session-based flows).
    | Note: this requires allowed_origins to list specific origins, not "*".
    |
    */
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
