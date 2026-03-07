<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection
    |--------------------------------------------------------------------------
    */
    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Configuration
    |--------------------------------------------------------------------------
    */
    'exchange' => [
        'name'        => env('RABBITMQ_EXCHANGE', 'product.events'),
        'type'        => 'topic',
        'passive'     => false,
        'durable'     => true,
        'auto_delete' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'name'        => env('RABBITMQ_QUEUE', 'product-service-queue'),
        'passive'     => false,
        'durable'     => true,
        'exclusive'   => false,
        'auto_delete' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Keys
    |--------------------------------------------------------------------------
    */
    'routing_keys' => [
        'product.created' => 'product.created',
        'product.updated' => 'product.updated',
        'product.deleted' => 'product.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Options
    |--------------------------------------------------------------------------
    */
    'connection_timeout'      => env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
    'read_write_timeout'      => env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    'heartbeat'               => env('RABBITMQ_HEARTBEAT', 60),
    'keepalive'               => env('RABBITMQ_KEEPALIVE', false),
];
