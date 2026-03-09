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

        'beanstalkd' => [
            'driver'      => 'beanstalkd',
            'host'        => 'localhost',
            'queue'       => 'default',
            'retry_after' => 90,
            'block_for'   => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver'      => 'sqs',
            'key'         => env('AWS_ACCESS_KEY_ID'),
            'secret'      => env('AWS_SECRET_ACCESS_KEY'),
            'prefix'      => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue'       => env('SQS_QUEUE', 'default'),
            'suffix'      => env('SQS_SUFFIX'),
            'region'      => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'      => 'redis',
            'connection'  => 'default',
            'queue'       => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for'   => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver'  => 'rabbitmq',
            'queue'   => env('RABBITMQ_QUEUE', 'auth_service'),
            'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

            'hosts' => [
                [
                    'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
                    'port'     => env('RABBITMQ_PORT', 5672),
                    'user'     => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost'    => env('RABBITMQ_VHOST', '/'),
                ],
            ],

            'options' => [
                'ssl_options' => [
                    'cafile'      => env('RABBITMQ_SSL_CAFILE'),
                    'local_cert'  => env('RABBITMQ_SSL_LOCALCERT'),
                    'local_key'   => env('RABBITMQ_SSL_LOCALKEY'),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE'),
                ],
                'queue' => [
                    'job'       => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
                    'exchange'  => env('RABBITMQ_EXCHANGE', 'saas_exchange'),
                    'exchange_type'    => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),
                    'exchange_routing_key' => env('RABBITMQ_EXCHANGE_ROUTING_KEY'),
                    'prioritize_delayed' => false,
                    'queue_max_priority' => null,
                    'reroute_failed'     => false,
                    'failed_exchange'    => null,
                    'failed_routing_key' => 'auth_service.failed',
                ],
            ],

            'worker' => env('RABBITMQ_WORKER', 'default'),
        ],

    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table'    => 'job_batches',
    ],

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table'    => 'failed_jobs',
    ],

];
