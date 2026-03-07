<?php

return [
    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://localhost:8002'),
    ],
    'product' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://localhost:8001'),
    ],
    'user' => [
        'url' => env('USER_SERVICE_URL', 'http://localhost:8004'),
    ],
    'webhook' => [
        'secret' => env('WEBHOOK_SECRET', ''),
    ],
];
