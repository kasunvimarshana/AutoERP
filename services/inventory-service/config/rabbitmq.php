<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection
    |--------------------------------------------------------------------------
    */
    'host'     => env('RABBITMQ_HOST', 'localhost'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Configuration
    |--------------------------------------------------------------------------
    | exchange_type options: direct | fanout | topic | headers
    */
    'exchange'      => env('RABBITMQ_EXCHANGE', 'inventory.events'),
    'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue'   => env('RABBITMQ_QUEUE', 'inventory-events'),
    'durable' => (bool) env('RABBITMQ_DURABLE', true),

    /*
    |--------------------------------------------------------------------------
    | Routing Keys
    |--------------------------------------------------------------------------
    */
    'routing_keys' => [
        'inventory_updated' => 'inventory.updated',
        'inventory_low'     => 'inventory.low',
        'inventory_depleted' => 'inventory.depleted',
        'product_created'   => 'product.created',
        'product_deleted'   => 'product.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Options
    |--------------------------------------------------------------------------
    */
    'heartbeat'          => (int) env('RABBITMQ_HEARTBEAT', 60),
    'connection_timeout' => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
    'read_write_timeout' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    'ssl_options'        => [],
];
