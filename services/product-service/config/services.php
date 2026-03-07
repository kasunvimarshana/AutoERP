<?php

return [
    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://localhost:8002'),
    ],
    'webhook' => [
        'secret' => env('WEBHOOK_SECRET', ''),
    ],
];
