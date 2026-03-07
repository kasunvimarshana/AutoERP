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
        'user_events' => [
            'name'        => env('RABBITMQ_QUEUE', 'user.events'),
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
        ],
    ],

    'routing_keys' => [
        'user.created' => 'user.created',
        'user.updated' => 'user.updated',
        'user.deleted' => 'user.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    */
    'connection_timeout'    => env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
    'read_write_timeout'    => env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    'heartbeat'             => env('RABBITMQ_HEARTBEAT', 60),
    'keepalive'             => env('RABBITMQ_KEEPALIVE', false),
];
