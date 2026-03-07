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
        'group_id' => env('KAFKA_GROUP_ID', 'order-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Topics
    |--------------------------------------------------------------------------
    */

    'topics' => [
        'orders' => env('TOPIC_ORDERS', 'orders.events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Downstream Microservices
    |--------------------------------------------------------------------------
    */

    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://user-service:8001'),
    ],

    'product_service' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service:8002'),
    ],

    'inventory_service' => [
        'url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8003'),
    ],

    'notification_service' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    */

    'payment' => [
        'default' => env('PAYMENT_GATEWAY', 'mock'),
        'url'     => env('PAYMENT_GATEWAY_URL', ''),
        'key'     => env('PAYMENT_GATEWAY_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'secret'      => env('WEBHOOK_SECRET', ''),
        'retry_times' => env('WEBHOOK_RETRY_TIMES', 3),
    ],
];
