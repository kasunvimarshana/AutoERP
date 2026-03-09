<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Message Broker Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the message broker driver and connection settings.
    | Supports: rabbitmq, kafka, log
    |
    */
    'driver' => env('MESSAGE_BROKER', 'log'),

    'rabbitmq' => [
        'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
        'port'     => (int) env('RABBITMQ_PORT', 5672),
        'user'     => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost'    => env('RABBITMQ_VHOST', '/'),
    ],

    'kafka' => [
        'brokers'  => env('KAFKA_BROKERS', 'kafka:9092'),
        'group_id' => env('KAFKA_GROUP_ID', 'inventory-service'),
    ],
];
