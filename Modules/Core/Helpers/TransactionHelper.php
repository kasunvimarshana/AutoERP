<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * TransactionHelper
 *
 * Helper for managing database transactions with retries and logging
 */
class TransactionHelper
{
    /**
     * Execute a callback within a transaction
     *
     * @param  int  $attempts  Number of retry attempts on deadlock
     *
     * @throws Throwable
     */
    public static function execute(Closure $callback, int $attempts = 3): mixed
    {
        $attempt = 0;

        while ($attempt < $attempts) {
            try {
                return DB::transaction($callback);
            } catch (Throwable $e) {
                $attempt++;

                // Check if it's a deadlock or lock wait timeout
                if (static::isDeadlock($e) && $attempt < $attempts) {
                    // Wait before retry (exponential backoff)
                    usleep(static::getBackoffTime($attempt));

                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException("Transaction failed after {$attempts} attempts");
    }

    /**
     * Execute with pessimistic write lock
     */
    public static function withLock(Closure $callback, string $table, $id): mixed
    {
        return static::execute(function () use ($callback, $table, $id) {
            // Acquire lock
            DB::table($table)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            return $callback();
        });
    }

    /**
     * Execute with shared read lock
     */
    public static function withSharedLock(Closure $callback, string $table, $id): mixed
    {
        return static::execute(function () use ($callback, $table, $id) {
            // Acquire shared lock
            DB::table($table)
                ->where('id', $id)
                ->sharedLock()
                ->first();

            return $callback();
        });
    }

    /**
     * Check if exception is a deadlock
     */
    protected static function isDeadlock(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'deadlock') ||
               str_contains($message, 'lock wait timeout');
    }

    /**
     * Get backoff time in microseconds
     */
    protected static function getBackoffTime(int $attempt): int
    {
        // Exponential backoff: 100ms, 200ms, 400ms, etc.
        return (int) (100000 * pow(2, $attempt - 1));
    }
}
