<?php

namespace App\Saga;

use App\Saga\Contracts\SagaInterface;
use Illuminate\Support\Facades\Log;

class SagaResult
{
    private bool   $successful;
    private string $error;
    private array  $data;
    private array  $compensationLog;

    public function __construct(bool $successful, string $error = '', array $data = [], array $compensationLog = [])
    {
        $this->successful      = $successful;
        $this->error           = $error;
        $this->data            = $data;
        $this->compensationLog = $compensationLog;
    }

    public function isSuccessful(): bool   { return $this->successful; }
    public function getError(): string     { return $this->error; }
    public function getData(string $key = null): mixed
    {
        return $key ? ($this->data[$key] ?? null) : $this->data;
    }
    public function getCompensationLog(): array { return $this->compensationLog; }
}

class SagaOrchestrator
{
    /**
     * Execute a list of saga steps in sequence.
     * On any step failure, compensating transactions run in reverse order.
     *
     * @param string        $sagaId   Unique saga identifier (correlates distributed logs).
     * @param SagaInterface[] $steps  Ordered list of saga steps.
     * @param array         $context  Initial context passed to steps.
     */
    public function run(string $sagaId, array $steps, array $context = []): SagaResult
    {
        $executed        = [];
        $compensationLog = [];

        Log::info("[Saga:{$sagaId}] Starting with " . count($steps) . " steps.");

        foreach ($steps as $step) {
            /** @var SagaInterface $step */
            $stepName = $step->getName();

            try {
                Log::info("[Saga:{$sagaId}] Executing step: {$stepName}");
                $context = $step->execute($context);
                $executed[] = $step;
                Log::info("[Saga:{$sagaId}] Step '{$stepName}' completed successfully.");
            } catch (\Throwable $e) {
                Log::error("[Saga:{$sagaId}] Step '{$stepName}' failed: {$e->getMessage()}");

                // Run compensating transactions in reverse
                $compensationLog = $this->compensate($sagaId, array_reverse($executed), $context);

                return new SagaResult(
                    false,
                    "Step '{$stepName}' failed: {$e->getMessage()}",
                    $context,
                    $compensationLog
                );
            }
        }

        Log::info("[Saga:{$sagaId}] All steps completed successfully.");

        return new SagaResult(true, '', $context, $compensationLog);
    }

    /**
     * Run compensating transactions in reverse order.
     *
     * @param string          $sagaId
     * @param SagaInterface[] $steps  Already executed steps (reversed).
     * @param array           $context
     */
    private function compensate(string $sagaId, array $steps, array &$context): array
    {
        $log = [];

        foreach ($steps as $step) {
            $stepName = $step->getName();

            try {
                Log::info("[Saga:{$sagaId}] Compensating step: {$stepName}");
                $step->compensate($context);
                $log[] = ['step' => $stepName, 'status' => 'compensated'];
                Log::info("[Saga:{$sagaId}] Compensation of '{$stepName}' done.");
            } catch (\Throwable $e) {
                Log::critical("[Saga:{$sagaId}] COMPENSATION FAILED for '{$stepName}': {$e->getMessage()}");
                $log[] = ['step' => $stepName, 'status' => 'compensation_failed', 'error' => $e->getMessage()];
                // Continue compensating remaining steps even if one fails
            }
        }

        return $log;
    }
}
