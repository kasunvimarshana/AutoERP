<?php

return [
    'auth' => [
        'url' => env('AUTH_SERVICE_URL', 'http://auth-service:9000'),
    ],
    'inventory' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:9000'),
    ],
    'saga_orchestrator' => [
        'url' => env('SAGA_ORCHESTRATOR_URL', 'http://saga-orchestrator:9000'),
    ],
];
