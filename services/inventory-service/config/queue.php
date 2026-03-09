<?php

return [

    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'      => 'database',
            'table'       => 'jobs',
            'queue'       => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => 'default',
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => 90,
            'block_for'    => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver'   => 'rabbitmq',
            'queue'    => env('RABBITMQ_QUEUE', 'inventory_queue'),
            'hosts'    => [
                [
                    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
                    'port'     => env('RABBITMQ_PORT', 5672),
                    'user'     => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost'    => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [
                    'cafile'      => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert'  => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_key'   => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'exchange'            => env('RABBITMQ_EXCHANGE', 'inventory_events'),
                    'exchange_type'       => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),
                    'exchange_routing_key'=> '',
                    'prioritize_delayed'  => false,
                    'queue_max_priority'  => null,
                    'queue'               => env('RABBITMQ_QUEUE', 'inventory_queue'),
                    'reroute_failed'      => true,
                    'failed_exchange'     => env('RABBITMQ_DLX_EXCHANGE', 'inventory_dlx'),
                    'failed_routing_key'  => 'failed',
                ],
            ],
        ],

        'kafka' => [
            'driver'         => 'kafka',
            'brokers'        => env('KAFKA_BROKERS', 'kafka:9092'),
            'consumer_group' => env('KAFKA_CONSUMER_GROUP', 'inventory-service'),
            'topics'         => env('KAFKA_TOPICS', 'inventory_events'),
            'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
            'sasl_mechanisms'   => env('KAFKA_SASL_MECHANISMS', ''),
            'sasl_username'     => env('KAFKA_SASL_USERNAME', ''),
            'sasl_password'     => env('KAFKA_SASL_PASSWORD', ''),
        ],

    ],

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table'    => 'failed_jobs',
    ],

];
