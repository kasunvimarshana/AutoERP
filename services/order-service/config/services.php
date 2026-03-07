<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Service
    |--------------------------------------------------------------------------
    | Configuration for the inventory-service HTTP client used by the Saga.
    */
    'inventory' => [
        'url'         => env('INVENTORY_SERVICE_URL', 'http://inventory-service:9000'),
        'timeout'     => (int) env('INVENTORY_SERVICE_TIMEOUT', 5),
        'retry_times' => (int) env('INVENTORY_SERVICE_RETRY_TIMES', 3),
        // Milliseconds between retries
        'retry_sleep' => (int) env('INVENTORY_SERVICE_RETRY_SLEEP', 500),
    ],
];
