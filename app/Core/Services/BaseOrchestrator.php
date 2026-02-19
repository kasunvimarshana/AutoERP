<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Orchestrator Service
 *
 * Provides common functionality for orchestrating operations across multiple modules.
 * Extend this class when you need to coordinate multiple services in a single business transaction.
 *
 * Features:
 * - Automatic transaction management
 * - Exception handling and rollback
 * - Step tracking for compensation
 * - Structured logging
 */
abstract class BaseOrchestrator
{
    /**
     * Track completed steps for compensation/rollback
     */
    protected array $completedSteps = [];

    /**
     * Whether a transaction is currently active
     */
    protected bool $transactionActive = false;

    /**
     * Execute an orchestrated operation with automatic transaction management
     *
     * @param  callable  $operation The operation to execute
     * @param  string  $operationName Name for logging
     * @return mixed The result of the operation
     *
     * @throws ServiceException
     */
    protected function executeInTransaction(callable $operation, string $operationName): mixed
    {
        $this->beginTransaction($operationName);

        try {
            $result = $operation();

            $this->commitTransaction($operationName);

            return $result;
        } catch (\Exception $e) {
            $this->rollbackTransaction($operationName, $e);

            throw new ServiceException(
                "{$operationName} failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Begin a new transaction
     */
    protected function beginTransaction(string $operationName): void
    {
        // Only begin a transaction if not already in one (e.g., from test framework)
        if (DB::transactionLevel() === 0) {
            DB::beginTransaction();
            $this->transactionActive = true;
        } else {
            // Reusing existing transaction
            $this->transactionActive = false;
        }
        
        $this->completedSteps = [];

        Log::info("{$operationName}: Transaction started", [
            'operation' => $operationName,
            'transaction_level' => DB::transactionLevel(),
            'managing_transaction' => $this->transactionActive,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Commit the current transaction
     */
    protected function commitTransaction(string $operationName): void
    {
        if (! $this->transactionActive) {
            return;
        }

        DB::commit();
        $this->transactionActive = false;

        Log::info("{$operationName}: Transaction committed successfully", [
            'operation' => $operationName,
            'completed_steps' => $this->completedSteps,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Rollback the current transaction
     */
    protected function rollbackTransaction(string $operationName, \Exception $exception): void
    {
        if (! $this->transactionActive) {
            return;
        }

        DB::rollBack();
        $this->transactionActive = false;

        Log::error("{$operationName}: Transaction rolled back", [
            'operation' => $operationName,
            'error' => $exception->getMessage(),
            'completed_steps' => $this->completedSteps,
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Allow subclasses to perform compensation
        $this->compensate();
    }

    /**
     * Record a completed step
     */
    protected function recordStep(string $stepName, array $context = []): void
    {
        $this->completedSteps[] = [
            'name' => $stepName,
            'context' => $context,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::debug("Step completed: {$stepName}", $context);
    }

    /**
     * Compensation logic for failed operations
     *
     * Override this method in subclasses to implement compensation logic
     * (e.g., release reserved resources, send reversal notifications, etc.)
     */
    protected function compensate(): void
    {
        // Default: no compensation
        // Subclasses can override to implement Saga pattern compensation
    }

    /**
     * Execute multiple operations in sequence with automatic rollback on failure
     *
     * @param  array<string, callable>  $steps Array of step name => callable pairs
     * @param  string  $operationName Name for logging
     * @return array Results from each step
     *
     * @throws ServiceException
     */
    protected function executeSteps(array $steps, string $operationName): array
    {
        return $this->executeInTransaction(function () use ($steps) {
            $results = [];

            foreach ($steps as $stepName => $step) {
                try {
                    $results[$stepName] = $step();
                    $this->recordStep($stepName, ['result_type' => gettype($results[$stepName])]);
                } catch (\Exception $e) {
                    Log::error("Step failed: {$stepName}", [
                        'error' => $e->getMessage(),
                        'previous_steps' => array_keys($results),
                    ]);

                    throw $e;
                }
            }

            return $results;
        }, $operationName);
    }

    /**
     * Execute an operation with retry logic
     *
     * @param  callable  $operation The operation to execute
     * @param  int  $maxAttempts Maximum number of attempts
     * @param  int  $delayMs Delay between attempts in milliseconds
     * @return mixed The result of the operation
     *
     * @throws ServiceException
     */
    protected function executeWithRetry(callable $operation, int $maxAttempts = 3, int $delayMs = 1000): mixed
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $maxAttempts) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $lastException = $e;

                Log::warning("Operation failed, attempt {$attempt}/{$maxAttempts}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                if ($attempt < $maxAttempts) {
                    usleep($delayMs * 1000); // Convert to microseconds
                }

                $attempt++;
            }
        }

        throw new ServiceException(
            "Operation failed after {$maxAttempts} attempts",
            previous: $lastException
        );
    }

    /**
     * Validate prerequisites before executing an operation
     *
     * @param  array<string, callable>  $validations Array of validation name => callable pairs
     *
     * @throws ServiceException If any validation fails
     */
    protected function validatePrerequisites(array $validations): void
    {
        foreach ($validations as $validationName => $validation) {
            try {
                $result = $validation();

                if ($result === false) {
                    throw new ServiceException("Prerequisite validation failed: {$validationName}");
                }
            } catch (\Exception $e) {
                throw new ServiceException(
                    "Prerequisite validation error ({$validationName}): {$e->getMessage()}",
                    previous: $e
                );
            }
        }
    }
}
