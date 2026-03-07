<?php

declare(strict_types=1);

namespace App\Application\Saga\Orchestrator;

use App\Application\Saga\Contracts\SagaInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Saga as SagaModel;

/**
 * Saga Orchestrator — executes a sequence of SagaInterface steps and performs
 * compensating transactions on failure.
 *
 * Usage:
 *   $orchestrator = new SagaOrchestrator([
 *       new ReserveStockStep($productRepo),
 *       new ProcessPaymentStep($paymentGateway),
 *       new CreateOrderStep($orderRepo),
 *   ]);
 *   $result = $orchestrator->run($initialContext);
 */
class SagaOrchestrator
{
    /** @var SagaInterface[] */
    private array $steps;

    /** @var SagaInterface[] Steps that have been successfully executed (for rollback). */
    private array $completedSteps = [];

    private string $sagaId;
    private ?SagaModel $sagaRecord = null;

    /**
     * @param SagaInterface[] $steps  Ordered list of saga steps.
     */
    public function __construct(array $steps)
    {
        $this->steps  = $steps;
        $this->sagaId = Str::uuid()->toString();
    }

    /**
     * Run all saga steps in sequence.
     *
     * @param  array<string, mixed> $context  Initial context payload.
     * @return array<string, mixed>           Final context after all steps.
     *
     * @throws \Throwable Re-throws after performing compensations.
     */
    public function run(array $context = []): array
    {
        $this->sagaRecord = $this->persistSagaStart($context);

        try {
            foreach ($this->steps as $step) {
                $stepName = $step->name();

                Log::info("[Saga:{$this->sagaId}] Executing step: {$stepName}");

                $context = $step->execute($context);

                $this->completedSteps[] = $step;
                $this->updateSagaStep($stepName, 'completed', $context);
            }

            $this->updateSagaStatus('completed', $context);

            Log::info("[Saga:{$this->sagaId}] All steps completed successfully.");

            return $context;
        } catch (\Throwable $e) {
            Log::error("[Saga:{$this->sagaId}] Step failed: {$e->getMessage()}. Starting compensation.");

            $this->compensate($context, $e);

            throw $e;
        }
    }

    /**
     * Execute compensating transactions in reverse order.
     */
    private function compensate(array $context, \Throwable $cause): void
    {
        $this->updateSagaStatus('compensating', $context);

        foreach (array_reverse($this->completedSteps) as $step) {
            $stepName = $step->name();

            try {
                Log::info("[Saga:{$this->sagaId}] Compensating step: {$stepName}");
                $step->compensate($context);
                Log::info("[Saga:{$this->sagaId}] Compensation succeeded for: {$stepName}");
            } catch (\Throwable $compensationError) {
                // Log compensation failure but continue rolling back other steps.
                Log::critical(
                    "[Saga:{$this->sagaId}] Compensation failed for step {$stepName}: "
                    . $compensationError->getMessage()
                );
            }
        }

        $this->updateSagaStatus('failed', $context, $cause->getMessage());

        Log::info("[Saga:{$this->sagaId}] Compensation complete.");
    }

    // -------------------------------------------------------------------------
    // Persistence helpers
    // -------------------------------------------------------------------------

    private function persistSagaStart(array $context): SagaModel
    {
        return SagaModel::create([
            'saga_id'    => $this->sagaId,
            'type'       => $context['saga_type'] ?? 'generic',
            'status'     => 'started',
            'context'    => $context,
            'tenant_id'  => $context['tenant_id'] ?? null,
            'started_at' => now(),
        ]);
    }

    private function updateSagaStep(string $stepName, string $status, array $context): void
    {
        if (!$this->sagaRecord) {
            return;
        }

        $steps = $this->sagaRecord->steps ?? [];
        $steps[] = [
            'name'       => $stepName,
            'status'     => $status,
            'timestamp'  => now()->toIso8601String(),
        ];

        $this->sagaRecord->update([
            'steps'   => $steps,
            'context' => $context,
        ]);
    }

    private function updateSagaStatus(string $status, array $context, ?string $errorMessage = null): void
    {
        if (!$this->sagaRecord) {
            return;
        }

        $update = [
            'status'  => $status,
            'context' => $context,
        ];

        if ($status === 'completed' || $status === 'failed') {
            $update['completed_at'] = now();
        }

        if ($errorMessage !== null) {
            $update['error_message'] = $errorMessage;
        }

        $this->sagaRecord->update($update);
    }

    public function getSagaId(): string
    {
        return $this->sagaId;
    }
}
