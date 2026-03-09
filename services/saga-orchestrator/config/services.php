<?php

return [
    'inventory' => ['url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:9000')],
    'order' => ['url' => env('ORDER_SERVICE_URL', 'http://order-service:9000')],
    'notification' => ['url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:9000')],
];
