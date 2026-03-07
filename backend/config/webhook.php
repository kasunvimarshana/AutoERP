<?php

return [
    /*
    | Signing secret for outgoing webhooks (HMAC SHA-256)
    */
    'secret' => env('WEBHOOK_SECRET', ''),

    /*
    | Number of delivery attempts before disabling a webhook
    */
    'retry_attempts' => (int) env('WEBHOOK_RETRY_ATTEMPTS', 3),

    /*
    | Delay in seconds between retry attempts
    */
    'retry_delay' => (int) env('WEBHOOK_RETRY_DELAY', 60),

    /*
    | HTTP timeout in seconds per delivery attempt
    */
    'timeout' => (int) env('WEBHOOK_TIMEOUT', 30),

    /*
    | Verify SSL certificates for delivery targets
    */
    'verify_ssl' => (bool) env('WEBHOOK_VERIFY_SSL', true),

    /*
    | Failures before auto-disabling a webhook endpoint
    */
    'max_failures' => 10,

    /*
    | Supported event types for webhook subscriptions
    */
    'events' => [
        'order.created',
        'order.confirmed',
        'order.cancelled',
        'order.delivered',
        'inventory.updated',
        'inventory.low_stock',
        'inventory.out_of_stock',
        'product.created',
        'product.updated',
        'product.deleted',
        'user.created',
        'webhook.test',
    ],
];
