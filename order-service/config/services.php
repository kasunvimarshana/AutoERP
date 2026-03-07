<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Downstream Microservice URLs (used by Saga steps)
    |--------------------------------------------------------------------------
    */

    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory_service:8000'),
    ],

    'notification' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification_service:3000'),
    ],

];
