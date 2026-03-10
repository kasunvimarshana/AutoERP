<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga;

use App\Domain\Order\Saga\SagaStepInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SagaOrchestrator
 *
 * Coordinates multi-step distributed transactions following the Saga pattern.
 *
 * Design:
 *  - Each step executes its forward action.
 *  - If any step throws, compensation runs in reverse order for all
 *    successfully completed steps.
 *  - Saga state is persisted in Redis so in-progress sagas survive pod restarts
 *    (for observability; the orchestrator itself is synchronous for simplicity).
 *
 * Example usage:
 *
 *   $orchestrator = new SagaOrchestrator();
 *   $orchestrator
 *       ->addStep(new ValidateOrderStep(...))
 *       ->addStep(new ReserveInventoryStep(...))
 *       ->addStep(new ProcessPaymentStep(...))
 *       ->addStep(new ConfirmOrderStep(...));
 *
 *   try {
 *       $context = $orchestrator->execute($sagaId, $initialContext);
 *   } catch (SagaFailedException $e) {
 *       // compensation already ran; saga state recorded in Redis
 *   }
 */
class SagaOrchestrator
{
    private const CACHE_PREFIX = 'saga:state:';
    private const CACHE_TTL    = 86400; // 24 hours

    /** @var SagaStepInterface[] */
    private array $steps = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Builder API
    // ─────────────────────────────────────────────────────────────────────────

    public function addStep(SagaStepInterface $step): static
    {
        $this->steps[] = $step;
        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Execution
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Execute the saga, running each step in order.
     *
     * @param  string               $sagaId   Correlation ID (e.g. order UUID)
     * @param  array<string, mixed> $context  Shared mutable context passed to each step
     * @return array<string, mixed>            Final enriched context
     *
     * @throws \App\Domain\Order\Saga\SagaFailedException
     */
    public function execute(string $sagaId, array $context): array
    {
        $this->persistState($sagaId, 'running', null, $context);

        $completedSteps = [];

        foreach ($this->steps as $step) {
            try {
                Log::info("Saga [{$sagaId}] executing step [{$step->name()}]");

                $step->execute($context);

                $completedSteps[] = $step;

                $this->persistState($sagaId, 'running', $step->name(), $context);

                Log::info("Saga [{$sagaId}] step [{$step->name()}] completed");

            } catch (\Throwable $e) {
                Log::error("Saga [{$sagaId}] step [{$step->name()}] FAILED: {$e->getMessage()}");

                $this->compensate($sagaId, $completedSteps, $context);

                $this->persistState($sagaId, 'failed', $step->name(), $context);

                throw new \App\Domain\Order\Saga\SagaFailedException(
                    sagaId:     $sagaId,
                    failedStep: $step->name(),
                    context:    $context,
                    previous:   $e
                );
            }
        }

        $this->persistState($sagaId, 'completed', null, $context);

        Log::info("Saga [{$sagaId}] completed successfully");

        return $context;
    }

    /**
     * Return the persisted status of a saga instance.
     *
     * @param  string $sagaId
     * @return array{status: string, current_step: string|null, context: array<string, mixed>}
     */
    public function getStatus(string $sagaId): array
    {
        return Cache::get(self::CACHE_PREFIX . $sagaId, [
            'status'       => 'unknown',
            'current_step' => null,
            'context'      => [],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Compensation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Run compensating actions in reverse order for all completed steps.
     *
     * @param  string               $sagaId
     * @param  SagaStepInterface[]  $completedSteps
     * @param  array<string, mixed> &$context
     * @return void
     */
    private function compensate(string $sagaId, array $completedSteps, array &$context): void
    {
        foreach (array_reverse($completedSteps) as $step) {
            try {
                Log::info("Saga [{$sagaId}] compensating step [{$step->name()}]");
                $step->compensate($context);
                Log::info("Saga [{$sagaId}] compensation for [{$step->name()}] complete");
            } catch (\Throwable $compensationError) {
                // Log but continue — we must attempt all compensations
                Log::critical(
                    "Saga [{$sagaId}] compensation for [{$step->name()}] FAILED: "
                    . $compensationError->getMessage()
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // State persistence (Redis)
    // ─────────────────────────────────────────────────────────────────────────

    private function persistState(
        string  $sagaId,
        string  $status,
        ?string $currentStep,
        array   $context
    ): void {
        Cache::put(self::CACHE_PREFIX . $sagaId, [
            'saga_id'      => $sagaId,
            'status'       => $status,
            'current_step' => $currentStep,
            'context'      => $context,
            'updated_at'   => now()->toIso8601String(),
        ], self::CACHE_TTL);
    }
}
