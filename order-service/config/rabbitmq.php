<?php

return [
    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),

    'exchanges' => [
        'commands' => 'saga.commands',
        'replies'  => 'saga.replies',
    ],

    'queues' => [
        'reserve_inventory' => 'reserve-inventory',
        'release_inventory' => 'release-inventory',
        'process_payment'   => 'process-payment',
        'refund_payment'    => 'refund-payment',
        'send_notification' => 'send-notification',
        'saga_replies'      => 'saga-replies',
    ],
];
