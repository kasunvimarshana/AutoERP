<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 *
 * Provides common service layer functionality including
 * transaction management and error handling.
 */
abstract class BaseService
{
    /**
     * Execute a database transaction
     *
     * @throws \Throwable
     */
    protected function transaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            Log::error('Transaction failed: '.$e->getMessage(), [
                'exception' => $e,
                'service' => static::class,
            ]);
            throw $e;
        }
    }

    /**
     * Log an error message
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge([
            'service' => static::class,
        ], $context));
    }

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge([
            'service' => static::class,
        ], $context));
    }

    /**
     * Log a warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge([
            'service' => static::class,
        ], $context));
    }
}
