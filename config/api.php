<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the rate limiting configuration for API endpoints.
    | Each limit profile specifies the maximum number of attempts allowed
    | within a decay period (in seconds).
    |
    */

    'rate_limits' => [
        /*
        |--------------------------------------------------------------------------
        | Default Rate Limit
        |--------------------------------------------------------------------------
        |
        | Default rate limit applied to API endpoints when no specific limit
        | is configured. Allows 60 requests per minute per user/IP.
        |
        */
        'default' => [
            'max_attempts' => env('API_RATE_LIMIT_DEFAULT', 60),
            'decay_seconds' => env('API_RATE_LIMIT_DEFAULT_DECAY', 60),
        ],

        /*
        |--------------------------------------------------------------------------
        | Authentication Rate Limits
        |--------------------------------------------------------------------------
        |
        | Stricter limits for authentication endpoints to prevent brute force
        | attacks and credential stuffing.
        |
        */
        'auth_login' => [
            'max_attempts' => env('API_RATE_LIMIT_AUTH_LOGIN', 5),
            'decay_seconds' => env('API_RATE_LIMIT_AUTH_LOGIN_DECAY', 300), // 5 attempts per 5 minutes
        ],

        'auth_register' => [
            'max_attempts' => env('API_RATE_LIMIT_AUTH_REGISTER', 3),
            'decay_seconds' => env('API_RATE_LIMIT_AUTH_REGISTER_DECAY', 3600), // 3 attempts per hour
        ],

        'auth_refresh' => [
            'max_attempts' => env('API_RATE_LIMIT_AUTH_REFRESH', 10),
            'decay_seconds' => env('API_RATE_LIMIT_AUTH_REFRESH_DECAY', 300), // 10 attempts per 5 minutes
        ],

        'password_reset' => [
            'max_attempts' => env('API_RATE_LIMIT_PASSWORD_RESET', 3),
            'decay_seconds' => env('API_RATE_LIMIT_PASSWORD_RESET_DECAY', 3600), // 3 attempts per hour
        ],

        /*
        |--------------------------------------------------------------------------
        | Read Operations Rate Limits
        |--------------------------------------------------------------------------
        |
        | Moderate limits for read-heavy operations like list/search endpoints.
        |
        */
        'read' => [
            'max_attempts' => env('API_RATE_LIMIT_READ', 120),
            'decay_seconds' => env('API_RATE_LIMIT_READ_DECAY', 60), // 120 requests per minute
        ],

        'search' => [
            'max_attempts' => env('API_RATE_LIMIT_SEARCH', 30),
            'decay_seconds' => env('API_RATE_LIMIT_SEARCH_DECAY', 60), // 30 searches per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Write Operations Rate Limits
        |--------------------------------------------------------------------------
        |
        | Conservative limits for write operations (create, update, delete)
        | to prevent abuse and maintain data integrity.
        |
        */
        'write' => [
            'max_attempts' => env('API_RATE_LIMIT_WRITE', 30),
            'decay_seconds' => env('API_RATE_LIMIT_WRITE_DECAY', 60), // 30 writes per minute
        ],

        'bulk_write' => [
            'max_attempts' => env('API_RATE_LIMIT_BULK_WRITE', 10),
            'decay_seconds' => env('API_RATE_LIMIT_BULK_WRITE_DECAY', 300), // 10 bulk operations per 5 minutes
        ],

        /*
        |--------------------------------------------------------------------------
        | Export/Report Rate Limits
        |--------------------------------------------------------------------------
        |
        | Strict limits for resource-intensive operations like reports and
        | exports to prevent system overload.
        |
        */
        'export' => [
            'max_attempts' => env('API_RATE_LIMIT_EXPORT', 5),
            'decay_seconds' => env('API_RATE_LIMIT_EXPORT_DECAY', 300), // 5 exports per 5 minutes
        ],

        'report' => [
            'max_attempts' => env('API_RATE_LIMIT_REPORT', 10),
            'decay_seconds' => env('API_RATE_LIMIT_REPORT_DECAY', 300), // 10 reports per 5 minutes
        ],

        /*
        |--------------------------------------------------------------------------
        | File Upload Rate Limits
        |--------------------------------------------------------------------------
        |
        | Limits for file upload operations to prevent storage abuse.
        |
        */
        'upload' => [
            'max_attempts' => env('API_RATE_LIMIT_UPLOAD', 20),
            'decay_seconds' => env('API_RATE_LIMIT_UPLOAD_DECAY', 300), // 20 uploads per 5 minutes
        ],

        /*
        |--------------------------------------------------------------------------
        | Notification Rate Limits
        |--------------------------------------------------------------------------
        |
        | Limits for sending notifications to prevent spam.
        |
        */
        'notification_send' => [
            'max_attempts' => env('API_RATE_LIMIT_NOTIFICATION', 50),
            'decay_seconds' => env('API_RATE_LIMIT_NOTIFICATION_DECAY', 3600), // 50 notifications per hour
        ],

        /*
        |--------------------------------------------------------------------------
        | API Key / Integration Rate Limits
        |--------------------------------------------------------------------------
        |
        | Higher limits for authenticated API clients (integrations, webhooks).
        |
        */
        'api_key' => [
            'max_attempts' => env('API_RATE_LIMIT_API_KEY', 1000),
            'decay_seconds' => env('API_RATE_LIMIT_API_KEY_DECAY', 60), // 1000 requests per minute
        ],

        'webhook' => [
            'max_attempts' => env('API_RATE_LIMIT_WEBHOOK', 100),
            'decay_seconds' => env('API_RATE_LIMIT_WEBHOOK_DECAY', 60), // 100 webhooks per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Administrative Rate Limits
        |--------------------------------------------------------------------------
        |
        | Relaxed limits for administrative operations.
        |
        */
        'admin' => [
            'max_attempts' => env('API_RATE_LIMIT_ADMIN', 300),
            'decay_seconds' => env('API_RATE_LIMIT_ADMIN_DECAY', 60), // 300 requests per minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Bypass
    |--------------------------------------------------------------------------
    |
    | IPs or user IDs that should bypass rate limiting. Use with caution.
    |
    */
    'bypass_ips' => env('API_RATE_LIMIT_BYPASS_IPS', []),
    'bypass_users' => env('API_RATE_LIMIT_BYPASS_USERS', []),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Headers
    |--------------------------------------------------------------------------
    |
    | Enable or disable rate limit headers in API responses.
    |
    */
    'headers_enabled' => env('API_RATE_LIMIT_HEADERS', true),
];
