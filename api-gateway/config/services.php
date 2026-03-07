<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Downstream Microservice URLs
    |--------------------------------------------------------------------------
    |
    | The gateway reads these from environment variables so each deployment
    | environment (local Docker, staging, production) can configure its own
    | service discovery without code changes.
    |
    */

    'auth' => [
        'url' => env('AUTH_SERVICE_URL', 'http://auth_service:8000'),
    ],

    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory_service:8000'),
    ],

    'orders' => [
        'url' => env('ORDER_SERVICE_URL', 'http://order_service:8000'),
    ],

    'notifications' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification_service:3000'),
    ],

    // Standard Laravel service config entries
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'resend'   => ['key' => env('RESEND_KEY')],
    'slack'    => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],
];
