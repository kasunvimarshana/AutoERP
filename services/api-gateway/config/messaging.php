<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Runtime Configuration Keys
    |--------------------------------------------------------------------------
    | Only these Laravel config keys can be overridden at runtime by tenants.
    | Keeping this list restrictive prevents tenants from modifying security-
    | sensitive settings such as encryption keys or authentication drivers.
    |
    */
    'allowed_runtime_keys' => [
        'cache.default',
        'database.default',
        'mail.default',
        'queue.default',
        'session.driver',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Broker Driver
    |--------------------------------------------------------------------------
    | Supported: "rabbitmq", "kafka"
    |
    */
    'driver' => env('MESSAGE_BROKER_DRIVER', 'rabbitmq'),

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_LOGIN', 'admin'),
        'password' => env('RABBITMQ_PASSWORD', 'secret'),
        'vhost' => env('RABBITMQ_VHOST', 'saas_vhost'),
        'exchanges' => [
            'saga' => 'saga.events',
            'inventory' => 'inventory.events',
            'order' => 'order.events',
            'payment' => 'payment.events',
            'notification' => 'notification.events',
        ],
    ],

    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
        'topics' => [
            'saga_events' => 'saga.events',
            'inventory_events' => 'inventory.events',
            'order_events' => 'order.events',
            'payment_events' => 'payment.events',
            'notification_events' => 'notification.events',
        ],
    ],
];
