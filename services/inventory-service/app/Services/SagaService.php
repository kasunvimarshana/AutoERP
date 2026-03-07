<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class SagaService
{
    /**
     * Registered compensation steps for the current saga.
     *
     * Each step is a callable that performs the rollback action.
     *
     * @var array<int, array{description: string, compensate: callable}>
     */
    private array $steps = [];

    private string $sagaId;

    private bool $completed = false;

    public function __construct()
    {
        $this->sagaId = uniqid('saga_', true);
    }

    /**
     * Begin a new saga context and return a fresh instance.
     */
    public static function beginSaga(): static
    {
        $instance = new static();

        Log::info('Saga started', ['saga_id' => $instance->sagaId]);

        return $instance;
    }

    /**
     * Register a saga step with its compensating action.
     *
     * @param  string   $description Human-readable description for logging
     * @param  callable $action      The forward action to execute
     * @param  callable $compensate  The rollback action if the saga fails
     * @return mixed The return value of the action
     * @throws Throwable If the action fails, compensation of previous steps is triggered
     */
    public function step(string $description, callable $action, callable $compensate): mixed
    {
        try {
            $result = $action();

            // Register compensation for this step (LIFO order)
            array_unshift($this->steps, [
                'description' => $description,
                'compensate'  => $compensate,
            ]);

            Log::info('Saga step completed', [
                'saga_id'     => $this->sagaId,
                'description' => $description,
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Saga step failed – triggering compensation', [
                'saga_id'     => $this->sagaId,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);

            $this->compensate();

            throw $e;
        }
    }

    /**
     * Mark the saga as successfully completed.
     */
    public function complete(): void
    {
        $this->completed = true;

        Log::info('Saga completed successfully', ['saga_id' => $this->sagaId]);
    }

    /**
     * Execute all registered compensation actions in reverse (LIFO) order.
     * Safe to call multiple times – subsequent calls are no-ops.
     */
    public function compensate(): void
    {
        if ($this->completed) {
            return;
        }

        Log::warning('Saga compensation triggered', [
            'saga_id' => $this->sagaId,
            'steps'   => count($this->steps),
        ]);

        foreach ($this->steps as $step) {
            try {
                ($step['compensate'])();

                Log::info('Saga compensation step executed', [
                    'saga_id'     => $this->sagaId,
                    'description' => $step['description'],
                ]);
            } catch (Throwable $e) {
                // Log but continue compensating remaining steps
                Log::critical('Saga compensation step failed – manual intervention required', [
                    'saga_id'     => $this->sagaId,
                    'description' => $step['description'],
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->steps = [];
    }

    /**
     * Return the unique saga identifier.
     */
    public function getSagaId(): string
    {
        return $this->sagaId;
    }
}
