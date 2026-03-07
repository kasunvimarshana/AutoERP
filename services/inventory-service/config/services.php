<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Service
    |--------------------------------------------------------------------------
    */
    'product_service' => [
        'url'     => env('PRODUCT_SERVICE_URL', 'http://product-service:8002'),
        'timeout' => (int) env('PRODUCT_SERVICE_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'timeout'         => (int) env('WEBHOOK_TIMEOUT', 10),
        'retry_attempts'  => (int) env('WEBHOOK_RETRY_ATTEMPTS', 3),
    ],
];
