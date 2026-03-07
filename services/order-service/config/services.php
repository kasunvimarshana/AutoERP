<?php

return [
    'user_service' => [
        'url'   => env('USER_SERVICE_URL', 'http://user-service:8001'),
        'token' => env('USER_SERVICE_TOKEN', ''),
    ],

    'product_service' => [
        'url'   => env('PRODUCT_SERVICE_URL', 'http://product-service:8002'),
        'token' => env('PRODUCT_SERVICE_TOKEN', ''),
    ],

    'inventory_service' => [
        'url'   => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8003'),
        'token' => env('INVENTORY_SERVICE_TOKEN', ''),
    ],

    'message_broker' => [
        'driver'    => env('MESSAGE_BROKER_DRIVER', 'null'),
        'rabbitmq'  => [
            'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
            'port'     => env('RABBITMQ_PORT', 5672),
            'user'     => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
            'exchange' => env('RABBITMQ_EXCHANGE', 'saas.events'),
        ],
        'kafka'     => [
            'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        ],
    ],

    'payment' => [
        'default' => env('PAYMENT_GATEWAY', 'mock'),
        'url'     => env('PAYMENT_GATEWAY_URL', ''),
        'key'     => env('PAYMENT_GATEWAY_KEY', ''),
    ],

    'webhook' => [
        'secret'      => env('WEBHOOK_SECRET', ''),
        'retry_times' => env('WEBHOOK_RETRY_TIMES', 3),
    ],
];
