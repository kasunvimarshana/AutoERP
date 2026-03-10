<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Settings
    |--------------------------------------------------------------------------
    | All values are read from environment variables at runtime, making
    | the connection fully dynamic and tenant-configurable.
    */
    'host'     => env('RABBITMQ_HOST',     'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT',     5672),
    'user'     => env('RABBITMQ_USER',     'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST',    'kvsaas'),

    'exchange' => [
        'events' => env('RABBITMQ_EXCHANGE_EVENTS', 'kvsaas.events'),
        'saga'   => env('RABBITMQ_EXCHANGE_SAGA',   'kvsaas.saga'),
    ],
];
