<?php

return [
    'auth_service' => [
        'url' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
        'introspection_endpoint' => '/api/v1/auth/introspect',
        'shared_secret' => env('AUTH_SERVICE_SHARED_SECRET', ''),
    ],
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'saas_events'),
    ],
];
