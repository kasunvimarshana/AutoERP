<?php

declare(strict_types=1);

namespace App\Contracts\Saga;

use App\Domain\Saga\Models\SagaTransaction;

/**
 * Saga Orchestrator Interface
 *
 * Defines the contract for coordinating distributed transactions.
 */
interface SagaOrchestratorInterface
{
    /**
     * Start a new saga transaction.
     *
     * @param  string  $sagaType  e.g., 'create_order'
     * @param  array<string, mixed>  $payload
     */
    public function start(string $sagaType, array $payload, string $tenantId): SagaTransaction;

    /**
     * Execute the next step in the saga.
     */
    public function executeNextStep(SagaTransaction $saga): bool;

    /**
     * Trigger compensation (rollback) for a failed saga.
     */
    public function compensate(SagaTransaction $saga, string $reason): void;

    /**
     * Get saga status.
     */
    public function getStatus(string $sagaId): SagaTransaction;
}
