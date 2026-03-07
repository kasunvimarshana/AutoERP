<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Message Broker Driver
    |--------------------------------------------------------------------------
    | Supported: "kafka", "rabbitmq", "null"
    */
    'driver' => env('BROKER_DRIVER', 'null'),

    'drivers' => [
        'kafka' => [
            'brokers'           => env('KAFKA_BROKERS', 'localhost:9092'),
            'group_id'          => env('KAFKA_GROUP_ID', 'inventory-service'),
            'socket_timeout_ms' => env('KAFKA_SOCKET_TIMEOUT_MS', 60000),
            'sasl_username'     => env('KAFKA_SASL_USERNAME'),
            'sasl_password'     => env('KAFKA_SASL_PASSWORD'),
        ],

        'rabbitmq' => [
            'host'     => env('RABBITMQ_HOST', 'localhost'),
            'port'     => env('RABBITMQ_PORT', 5672),
            'user'     => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
            'exchange' => env('RABBITMQ_EXCHANGE', 'inventory_events'),
        ],
    ],
];
