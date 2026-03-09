<?php

declare(strict_types=1);

return [
    'order_service' => env('ORDER_SERVICE_URL', 'http://order_service'),
    'inventory_service' => env('INVENTORY_SERVICE_URL', 'http://inventory_service'),
    'payment_service' => env('PAYMENT_SERVICE_URL', 'http://payment_service:3000'),
    'notification_service' => env('NOTIFICATION_SERVICE_URL', 'http://notification_service'),
];
