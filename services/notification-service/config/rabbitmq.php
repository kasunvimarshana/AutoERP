<?php

return [
    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'saga.events'),
    'commands_exchange' => env('RABBITMQ_COMMANDS_EXCHANGE', 'saga.commands'),
    'queue'    => env('RABBITMQ_QUEUE', 'notification.commands'),
];
