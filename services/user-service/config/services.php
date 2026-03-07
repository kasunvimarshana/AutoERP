<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal Service-to-Service Key
    |--------------------------------------------------------------------------
    |
    | Shared secret used when one microservice calls another. Set via env.
    |
    */

    'internal_key' => env('INTERNAL_SERVICE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Message Broker
    |--------------------------------------------------------------------------
    |
    | 'null'     – no-op (testing / local dev)
    | 'rabbitmq' – RabbitMQ via php-amqplib
    | 'kafka'    – Apache Kafka via ext-rdkafka
    |
    */

    'message_broker' => env('MESSAGE_BROKER', 'null'),

    'rabbitmq' => [
        'host'     => env('RABBITMQ_HOST',     'rabbitmq'),
        'port'     => (int) env('RABBITMQ_PORT', 5672),
        'user'     => env('RABBITMQ_USER',     'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost'    => env('RABBITMQ_VHOST',    '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'saas.events'),
    ],

    'kafka' => [
        'brokers'  => env('KAFKA_BROKERS',  'kafka:9092'),
        'group_id' => env('KAFKA_GROUP_ID', 'user-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Downstream Microservices
    |--------------------------------------------------------------------------
    */

    'order_service' => [
        'url' => env('ORDER_SERVICE_URL', 'http://order-service'),
    ],

    'inventory_service' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service'),
    ],

    'notification_service' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party OAuth / SSO Providers
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT', '/auth/google/callback'),
    ],

    'microsoft' => [
        'client_id'     => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect'      => env('MICROSOFT_REDIRECT', '/auth/microsoft/callback'),
    ],

    'github' => [
        'client_id'     => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect'      => env('GITHUB_REDIRECT', '/auth/github/callback'),
    ],

];
