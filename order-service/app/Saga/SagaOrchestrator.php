<?php

declare(strict_types=1);

namespace App\Saga;

use App\Contracts\SagaOrchestratorInterface;
use App\Contracts\SagaStepInterface;
use App\Exceptions\SagaException;
use App\Exceptions\SagaStepException;
use App\Models\SagaLog;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration-based Saga executor.
 *
 * Maintains an ordered list of SagaStepInterface implementations
 * and a stack of completed steps for compensation.
 *
 * Flow:
 *   1. Iterate steps in registration order (forward).
 *   2. On success: push step onto compensation stack & log.
 *   3. On failure: iterate compensation stack in reverse (LIFO) & log.
 *
 * All state transitions are persisted to the saga_logs table so
 * that a crashed orchestrator can be resumed by a recovery job.
 */
final class SagaOrchestrator implements SagaOrchestratorInterface
{
    /** @var SagaStepInterface[] */
    private array $steps = [];

    /** @var SagaStepInterface[] LIFO stack of completed steps */
    private array $completedSteps = [];

    /** {@inheritDoc} */
    public function addStep(SagaStepInterface $step): static
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(SagaContext $context): void
    {
        $sagaId  = $context->get('saga_id');
        $orderId = $context->get('order_id');

        Log::info("[Saga:{$sagaId}] Starting saga execution", ['order_id' => $orderId]);

        foreach ($this->steps as $step) {
            try {
                Log::info("[Saga:{$sagaId}] Executing step: {$step->getName()}");

                $step->execute($context);
                $this->completedSteps[] = $step;

                // Persist step success to saga log
                $this->logStep($sagaId, $orderId, $step->getName(), 'completed');

                Log::info("[Saga:{$sagaId}] Step completed: {$step->getName()}");

            } catch (SagaStepException $e) {
                Log::error("[Saga:{$sagaId}] Step failed: {$step->getName()}", [
                    'error' => $e->getMessage(),
                ]);

                // Persist step failure
                $this->logStep($sagaId, $orderId, $step->getName(), 'failed', $e->getMessage());

                // Trigger compensations in reverse order
                $this->compensate($context, $sagaId, $orderId);

                throw new SagaException(
                    "Saga {$sagaId} failed at step [{$step->getName()}]: {$e->getMessage()}",
                    previous: $e
                );
            }
        }

        Log::info("[Saga:{$sagaId}] Saga completed successfully");
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    /**
     * Execute compensations in LIFO order.
     */
    private function compensate(SagaContext $context, string $sagaId, ?string $orderId): void
    {
        Log::warning("[Saga:{$sagaId}] Starting compensation (LIFO order)");

        foreach (array_reverse($this->completedSteps) as $step) {
            try {
                Log::info("[Saga:{$sagaId}] Compensating: {$step->getName()}");
                $step->compensate($context);
                $this->logStep($sagaId, $orderId, $step->getName(), 'compensated');
            } catch (\Throwable $e) {
                // Log compensation failure but continue compensating other steps.
                // A background job should retry failed compensations.
                Log::error("[Saga:{$sagaId}] Compensation failed for step: {$step->getName()}", [
                    'error' => $e->getMessage(),
                ]);
                $this->logStep($sagaId, $orderId, $step->getName(), 'compensation_failed', $e->getMessage());
            }
        }

        Log::warning("[Saga:{$sagaId}] Compensation completed");
    }

    /**
     * Persist a saga step state transition to the database.
     */
    private function logStep(
        string  $sagaId,
        ?string $orderId,
        string  $stepName,
        string  $status,
        ?string $errorMessage = null
    ): void {
        try {
            SagaLog::create([
                'saga_id'       => $sagaId,
                'order_id'      => $orderId,
                'step_name'     => $stepName,
                'status'        => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: log to application log if DB write fails
            Log::error("Failed to write saga log entry", [
                'saga_id'  => $sagaId,
                'step'     => $stepName,
                'status'   => $status,
                'db_error' => $e->getMessage(),
            ]);
        }
    }
}
