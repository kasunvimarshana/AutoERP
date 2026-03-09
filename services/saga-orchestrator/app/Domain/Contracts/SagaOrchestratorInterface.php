<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Saga Orchestrator Interface
 *
 * Contract for the central saga coordination service.
 */
interface SagaOrchestratorInterface
{
    /**
     * Get the current status of a saga.
     */
    public function getStatus(string $sagaId): ?array;

    /**
     * Trigger compensation for a failed saga.
     */
    public function compensate(string $sagaId, string $failedStep, array $context): void;
}
