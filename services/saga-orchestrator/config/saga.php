<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Saga Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum retries per saga step before failing
    'max_retries' => (int) env('SAGA_MAX_RETRIES', 3),

    // Timeout per step in seconds
    'step_timeout' => (int) env('SAGA_STEP_TIMEOUT', 30),

    // Registered saga types
    'definitions' => [
        'create_order' => \App\Domain\Saga\Definitions\CreateOrderSaga::class,
    ],
];
