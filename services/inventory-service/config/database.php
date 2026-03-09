<?php

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DATABASE_URL'),
            'host'           => env('DB_HOST', 'postgres'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'inventory_db'),
            'username'       => env('DB_USERNAME', 'inventory_user'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => env('DB_SCHEMA', 'public'),
            'sslmode'        => env('DB_SSLMODE', 'prefer'),
        ],

        /*
        |----------------------------------------------------------------------
        | Tenant database connections are resolved dynamically at runtime by
        | TenantMiddleware via the `tenant_<id>` connection name convention.
        |----------------------------------------------------------------------
        */
        'tenant_template' => [
            'driver'         => 'pgsql',
            'host'           => env('TENANT_DB_HOST', env('DB_HOST', 'postgres')),
            'port'           => env('TENANT_DB_PORT', env('DB_PORT', '5432')),
            'database'       => null,
            'username'       => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'inventory_user')),
            'password'       => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => env('DB_SSLMODE', 'prefer'),
        ],

    ],

    'migrations' => 'migrations',

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', 'inventory_service_database_'),
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '2'),
        ],

        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '3'),
        ],

    ],

];
