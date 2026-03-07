<?php

return [
    /*
    | Execute saga steps asynchronously (via queue workers)
    */
    'async' => (bool) env('SAGA_ASYNC', false),

    /*
    | Maximum seconds before a saga is considered timed out
    */
    'timeout' => (int) env('SAGA_TIMEOUT', 300),

    /*
    | Maximum retries per step before triggering compensation
    */
    'max_retries' => (int) env('SAGA_MAX_RETRIES', 3),

    /*
    | Log all saga state transitions
    */
    'log_transitions' => true,

    /*
    | Queue to use for async saga steps
    */
    'queue' => 'sagas',

    /*
    | Available saga pipelines
    */
    'pipelines' => [
        'place_order' => [
            \App\Saga\Steps\CreateOrderStep::class,
            \App\Saga\Steps\ReserveInventoryStep::class,
            \App\Saga\Steps\ProcessPaymentStep::class,
        ],
    ],
];
