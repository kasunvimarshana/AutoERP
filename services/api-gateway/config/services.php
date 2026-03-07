<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LaravelSAGA Microservices
    |--------------------------------------------------------------------------
    */

    'order_service' => [
        'url'     => env('ORDER_SERVICE_URL', 'http://order-service:8001'),
        'timeout' => env('ORDER_SERVICE_TIMEOUT', 10),
    ],

    'inventory_service' => [
        'url'     => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8002'),
        'timeout' => env('INVENTORY_SERVICE_TIMEOUT', 10),
    ],

    'payment_service' => [
        'url'     => env('PAYMENT_SERVICE_URL', 'http://payment-service:8003'),
        'timeout' => env('PAYMENT_SERVICE_TIMEOUT', 15),
    ],

    'notification_service' => [
        'url'     => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:8004'),
        'timeout' => env('NOTIFICATION_SERVICE_TIMEOUT', 5),
    ],

];
