<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Configuration for external services used by the Product Service.
    |
    */

    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8002'),
    ],
];
