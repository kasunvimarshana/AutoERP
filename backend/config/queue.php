<?php

return [
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'       => 'database',
            'connection'   => null,
            'table'        => env('DB_QUEUE_TABLE', 'jobs'),
            'queue'        => 'default',
            'retry_after'  => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'    => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver'   => 'rabbitmq',
            'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
            'port'     => (int) env('RABBITMQ_PORT', 5672),
            'login'    => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
            'queue'    => env('RABBITMQ_QUEUE', 'default'),
            'exchange' => [
                'name'        => env('RABBITMQ_EXCHANGE_NAME', 'inventory.exchange'),
                'type'        => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
            ],
            'options' => [
                'heartbeat'           => 60,
                'connection_timeout'  => 10,
                'read_write_timeout'  => 10,
            ],
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'job_batches',
    ],

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],
];
