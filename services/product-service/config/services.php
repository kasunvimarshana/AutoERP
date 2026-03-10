<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Service URLs
    |--------------------------------------------------------------------------
    | Internal service discovery configuration.  All values are read from
    | environment variables so they can be overridden per environment.
    */
    'auth_service' => [
        'url' => env('AUTH_SERVICE_URL', 'http://auth-service:8001'),
    ],

    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://user-service:8002'),
    ],

    'product_service' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service:8003'),
    ],

    'inventory_service' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8004'),
    ],

    'order_service' => [
        'url' => env('ORDER_SERVICE_URL', 'http://order-service:8005'),
    ],
];
