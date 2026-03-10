<?php

return [
    'payment' => [
        'url' => env('PAYMENT_SERVICE_URL', 'http://payment-service:8000'),
    ],
    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8000'),
    ],
    'notification' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:3001'),
    ],
];
