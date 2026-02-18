<?php

declare(strict_types=1);

namespace App\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Use Case
 * 
 * Represents an application-level operation that orchestrates
 * domain services, repositories, and business logic to fulfill
 * a specific user or system intent.
 * 
 * Use Cases follow the Single Responsibility Principle - each
 * use case handles exactly one business operation.
 */
abstract class BaseUseCase
{
    /**
     * Execute the use case
     * 
     * @return mixed The result of the use case execution
     */
    abstract public function execute(...$args): mixed;

    /**
     * Execute use case within a database transaction
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Log use case execution
     */
    protected function logExecution(string $message, array $context = []): void
    {
        Log::info(
            sprintf('[UseCase: %s] %s', class_basename($this), $message),
            $context
        );
    }

    /**
     * Log use case error
     */
    protected function logError(string $message, \Throwable $exception): void
    {
        Log::error(
            sprintf('[UseCase: %s] %s', class_basename($this), $message),
            [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }

    /**
     * Validate input data
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Handle use case execution with error handling
     */
    public function handle(...$args): mixed
    {
        try {
            $this->logExecution('Starting execution', ['args' => $args]);
            
            $result = $this->execute(...$args);
            
            $this->logExecution('Execution completed successfully');
            
            return $result;
        } catch (\Throwable $exception) {
            $this->logError('Execution failed', $exception);
            throw $exception;
        }
    }
}
