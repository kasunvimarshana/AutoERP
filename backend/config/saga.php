<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Saga Configuration
    |--------------------------------------------------------------------------
    */

    /*
     * Default timeout in seconds for a single saga execution.
     */
    'timeout' => (int) env('SAGA_TIMEOUT', 30),

    /*
     * Maximum number of compensation retry attempts per step.
     */
    'compensation_retries' => (int) env('SAGA_COMPENSATION_RETRIES', 3),

    /*
     * Whether to persist saga state to the database.
     * Disable in tests to avoid DB overhead.
     */
    'persist' => (bool) env('SAGA_PERSIST', true),

    /*
     * Log channel for saga events (null = default channel).
     */
    'log_channel' => env('SAGA_LOG_CHANNEL', null),
];
