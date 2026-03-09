<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Saga Interface
 * 
 * Contract for Saga orchestration with distributed transaction support.
 */
interface SagaInterface
{
    /**
     * Execute the saga workflow.
     */
    public function execute(array $payload): array;

    /**
     * Execute a compensation (rollback) for a failed step.
     */
    public function compensate(string $sagaId, string $failedStep, array $context): void;

    /**
     * Get the current status of a saga transaction.
     */
    public function getStatus(string $sagaId): array;
}
