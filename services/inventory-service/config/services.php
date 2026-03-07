<?php

return [
    'product' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://localhost:8001'),
    ],
    'webhook' => [
        'secret' => env('WEBHOOK_SECRET', ''),
    ],
];
