<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to RabbitMQ message broker.
    | Used by RabbitMQService to publish product events for
    | cross-service communication.
    |
    */

    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
    'port'     => env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'product_events'),
    'queue'    => env('RABBITMQ_QUEUE', 'product_queue'),
];
