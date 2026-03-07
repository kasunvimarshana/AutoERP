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
    | Exchange & Routing
    |--------------------------------------------------------------------------
    */
    'exchange' => [
        'name'        => env('RABBITMQ_EXCHANGE', 'saas.events'),
        'type'        => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),
        'passive'     => false,
        'durable'     => true,
        'auto_delete' => false,
    ],

    'queues' => [
        'inventory_events' => [
            'name'        => env('RABBITMQ_INVENTORY_QUEUE', 'inventory.events'),
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
        ],
        'product_events' => [
            'name'        => env('RABBITMQ_PRODUCT_QUEUE', 'product.events'),
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
        ],
    ],

    'routing_keys' => [
        'inventory' => [
            'updated' => 'inventory.updated',
            'created' => 'inventory.created',
            'deleted' => 'inventory.deleted',
        ],
        'stock' => [
            'adjusted'  => 'stock.adjusted',
            'reserved'  => 'stock.reserved',
            'released'  => 'stock.released',
            'low'       => 'stock.low',
        ],
        'product' => [
            'created' => 'product.created',
            'deleted' => 'product.deleted',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Consumer Bindings
    |--------------------------------------------------------------------------
    | Routing key patterns to bind the inventory queue to.
    */
    'bindings' => [
        'product.created',
        'product.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    */
    'connection_timeout' => env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
    'read_write_timeout' => env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    'heartbeat'          => env('RABBITMQ_HEARTBEAT', 60),
    'keepalive'          => env('RABBITMQ_KEEPALIVE', false),
];
