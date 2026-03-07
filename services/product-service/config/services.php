<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microservice Endpoints
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        'url'     => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8003'),
        'timeout' => (int) env('INVENTORY_SERVICE_TIMEOUT', 5),
        'retries' => (int) env('INVENTORY_SERVICE_RETRIES', 2),
    ],

    'user' => [
        'url'     => env('USER_SERVICE_URL', 'http://user-service:8001'),
        'timeout' => (int) env('USER_SERVICE_TIMEOUT', 5),
        'retries' => (int) env('USER_SERVICE_RETRIES', 2),
    ],

    'webhook' => [
        'url'     => env('WEBHOOK_SERVICE_URL', 'http://webhook-service:8005'),
        'timeout' => (int) env('WEBHOOK_SERVICE_TIMEOUT', 10),
        'secret'  => env('WEBHOOK_SECRET', ''),
    ],
];
