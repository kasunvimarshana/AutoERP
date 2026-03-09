<?php

declare(strict_types=1);

return [
    'driver' => env('MESSAGE_BROKER_DRIVER', 'rabbitmq'),
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_LOGIN', 'admin'),
        'password' => env('RABBITMQ_PASSWORD', 'secret'),
        'vhost' => env('RABBITMQ_VHOST', 'saas_vhost'),
        'exchange' => 'saga.events',
    ],
    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
    ],
];
