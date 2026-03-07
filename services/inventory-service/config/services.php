<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal Service-to-Service Key
    |--------------------------------------------------------------------------
    |
    | Shared secret used when one microservice calls another.
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
        'brokers'  => env('KAFKA_BROKERS',   'kafka:9092'),
        'group_id' => env('KAFKA_GROUP_ID',  'inventory-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Downstream Microservices
    |--------------------------------------------------------------------------
    */

    'product_service' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service'),
    ],

    'order_service' => [
        'url' => env('ORDER_SERVICE_URL', 'http://order-service'),
    ],

    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://user-service'),
    ],

    'notification_service' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service'),
    ],

];
