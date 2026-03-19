<?php

namespace Shared\Saga;

use Exception;
use Illuminate\Support\Facades\Log;

class SagaOrchestrator implements SagaOrchestratorInterface
{
    protected array $activeSagas = [];

    public function start(string $sagaType, array $payload): void
    {
        $sagaId = uniqid('saga_', true);
        
        // In a real system, we would resolve the Saga class via a factory
        // and persist its state in a database/Redis.
        Log::info("Starting Saga: {$sagaType}", ['saga_id' => $sagaId, 'payload' => $payload]);
        
        $this->executeNextStep($sagaId, $sagaType, $payload, 0);
    }

    protected function executeNextStep(string $sagaId, string $sagaType, array $payload, int $stepIndex): void
    {
        // Conceptual: Get steps from Saga definition
        // For demonstration, we assume steps are injected or retrieved from a registry
        Log::info("Executing step {$stepIndex} for Saga {$sagaId}");
    }

    public function handleStepCompletion(string $sagaId, string $step, array $result): void
    {
        Log::info("Step {$step} completed for Saga {$sagaId}", ['result' => $result]);
        // Move to next step or finish
    }

    public function handleStepFailure(string $sagaId, string $step, string $error): void
    {
        Log::error("Step {$step} failed for Saga {$sagaId}", ['error' => $error]);
        $this->startCompensatingActions($sagaId, $step);
    }

    protected function startCompensatingActions(string $sagaId, string $failedStep): void
    {
        Log::info("Starting compensation for Saga {$sagaId} from step {$failedStep}");
        // Reverse through completed steps and call compensate()
    }
}
