<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notification channels, templates, and delivery.
    | All values use environment variables with sensible defaults.
    |
    */

    'channels' => [
        /*
         * Available notification channels
         */
        'available' => [
            'email' => env('NOTIFICATION_CHANNEL_EMAIL_ENABLED', true),
            'sms' => env('NOTIFICATION_CHANNEL_SMS_ENABLED', false),
            'push' => env('NOTIFICATION_CHANNEL_PUSH_ENABLED', false),
            'database' => env('NOTIFICATION_CHANNEL_DATABASE_ENABLED', true),
            'slack' => env('NOTIFICATION_CHANNEL_SLACK_ENABLED', false),
        ],

        /*
         * Default notification channel
         */
        'default' => env('NOTIFICATION_DEFAULT_CHANNEL', 'database'),
    ],

    'delivery' => [
        /*
         * Queue notifications for async delivery
         */
        'queue_enabled' => (bool) env('NOTIFICATION_QUEUE_ENABLED', true),

        /*
         * Queue connection for notifications
         */
        'queue_connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'default'),

        /*
         * Queue name for notifications
         */
        'queue_name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),

        /*
         * Maximum retry attempts for failed notifications
         */
        'max_retries' => (int) env('NOTIFICATION_MAX_RETRIES', 3),

        /*
         * Retry delay in seconds between attempts
         */
        'retry_delay' => (int) env('NOTIFICATION_RETRY_DELAY', 300),

        /*
         * Timeout for notification delivery in seconds
         */
        'timeout' => (int) env('NOTIFICATION_TIMEOUT', 30),
    ],

    'priority' => [
        /*
         * Priority levels and their queue priorities
         */
        'levels' => [
            'low' => (int) env('NOTIFICATION_PRIORITY_LOW', 10),
            'normal' => (int) env('NOTIFICATION_PRIORITY_NORMAL', 5),
            'high' => (int) env('NOTIFICATION_PRIORITY_HIGH', 1),
            'urgent' => (int) env('NOTIFICATION_PRIORITY_URGENT', 0),
        ],
    ],

    'templates' => [
        /*
         * Template rendering engine
         */
        'engine' => env('NOTIFICATION_TEMPLATE_ENGINE', 'blade'),

        /*
         * Cache compiled templates
         */
        'cache_enabled' => (bool) env('NOTIFICATION_TEMPLATE_CACHE', true),

        /*
         * Template cache TTL in seconds
         */
        'cache_ttl' => (int) env('NOTIFICATION_TEMPLATE_CACHE_TTL', 3600),
    ],

    'cleanup' => [
        /*
         * Auto-cleanup read notifications after days
         */
        'read_after_days' => (int) env('NOTIFICATION_CLEANUP_READ_DAYS', 90),

        /*
         * Auto-cleanup delivered notifications after days
         */
        'delivered_after_days' => (int) env('NOTIFICATION_CLEANUP_DELIVERED_DAYS', 180),

        /*
         * Auto-cleanup notification logs after days
         */
        'logs_after_days' => (int) env('NOTIFICATION_CLEANUP_LOGS_DAYS', 365),

        /*
         * Enable automatic cleanup
         */
        'auto_cleanup_enabled' => (bool) env('NOTIFICATION_AUTO_CLEANUP', true),
    ],

    'rate_limiting' => [
        /*
         * Enable rate limiting for notifications
         */
        'enabled' => (bool) env('NOTIFICATION_RATE_LIMIT_ENABLED', true),

        /*
         * Maximum notifications per user per hour
         */
        'max_per_hour' => (int) env('NOTIFICATION_RATE_LIMIT_PER_HOUR', 100),

        /*
         * Maximum notifications per user per day
         */
        'max_per_day' => (int) env('NOTIFICATION_RATE_LIMIT_PER_DAY', 500),
    ],

    'preferences' => [
        /*
         * Allow users to configure notification preferences
         */
        'user_configurable' => (bool) env('NOTIFICATION_USER_PREFERENCES', true),

        /*
         * Default user preferences
         */
        'defaults' => [
            'email_enabled' => (bool) env('NOTIFICATION_DEFAULT_EMAIL', true),
            'sms_enabled' => (bool) env('NOTIFICATION_DEFAULT_SMS', false),
            'push_enabled' => (bool) env('NOTIFICATION_DEFAULT_PUSH', true),
            'database_enabled' => (bool) env('NOTIFICATION_DEFAULT_DATABASE', true),
        ],
    ],

    'sms' => [
        /*
         * Enable SMS notifications
         */
        'enabled' => (bool) env('NOTIFICATION_SMS_ENABLED', false),

        /*
         * SMS provider (twilio, sns)
         */
        'provider' => env('NOTIFICATION_SMS_PROVIDER', 'twilio'),

        /*
         * Twilio configuration
         */
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_PHONE_FROM'),
            'status_callback' => env('TWILIO_STATUS_CALLBACK_URL'),
        ],

        /*
         * AWS SNS configuration
         */
        'sns' => [
            'access_key_id' => env('AWS_SNS_KEY'),
            'secret_access_key' => env('AWS_SNS_SECRET'),
            'region' => env('AWS_SNS_REGION', 'us-east-1'),
        ],

        /*
         * SMS queue settings
         */
        'queue' => env('NOTIFICATION_EMAIL_QUEUE', 'default'),

        /*
         * SMS retry settings
         */
        'retry' => (int) env('NOTIFICATION_EMAIL_RETRY', 3),
    ],

    'push' => [
        /*
         * Enable push notifications
         */
        'enabled' => (bool) env('NOTIFICATION_PUSH_ENABLED', false),

        /*
         * Push notification provider (fcm, apns)
         */
        'provider' => env('NOTIFICATION_PUSH_PROVIDER', 'fcm'),

        /*
         * Firebase Cloud Messaging (FCM) configuration
         */
        'fcm' => [
            'api_key' => env('FCM_API_KEY'),
            'project_id' => env('FCM_PROJECT_ID'),
            'sender_id' => env('FCM_SENDER_ID'),
            'server_key' => env('FCM_SERVER_KEY'),
        ],

        /*
         * Apple Push Notification Service (APNS) configuration
         */
        'apns' => [
            'certificate_path' => env('APNS_CERTIFICATE_PATH'),
            'certificate_passphrase' => env('APNS_CERTIFICATE_PASSPHRASE'),
            'production' => (bool) env('APNS_PRODUCTION', false),
        ],

        /*
         * Push notification default options
         */
        'options' => [
            'priority' => env('NOTIFICATION_PUSH_PRIORITY', 'high'),
            'ttl' => (int) env('NOTIFICATION_PUSH_TTL', 86400), // 24 hours
            'sound' => env('NOTIFICATION_PUSH_SOUND', 'default'),
        ],
    ],
];
