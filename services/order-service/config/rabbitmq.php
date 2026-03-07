<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection
    |--------------------------------------------------------------------------
    */
    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'saga_user'),
    'password' => env('RABBITMQ_PASSWORD', 'saga_password'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchanges
    |--------------------------------------------------------------------------
    */
    'exchanges' => [
        'commands' => 'saga.commands',
        'events'   => 'saga.events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'replies' => env('RABBITMQ_REPLIES_QUEUE', 'saga.orchestrator.replies'),
    ],
];
