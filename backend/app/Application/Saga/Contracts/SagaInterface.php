<?php

declare(strict_types=1);

namespace App\Application\Saga\Contracts;

/**
 * Contract that every saga step must implement.
 *
 * A SagaStep is a single transactional unit within a saga. Each step must
 * implement both a forward `execute()` action and a compensating `compensate()`
 * action so that the SagaOrchestrator can roll back completed steps when a
 * later step fails.
 */
interface SagaInterface
{
    /**
     * Execute the saga step forward.
     *
     * @param  array<string, mixed> $context  Shared saga context / payload.
     * @return array<string, mixed>           Updated context after execution.
     *
     * @throws \Throwable On step failure.
     */
    public function execute(array $context): array;

    /**
     * Compensate (roll back) the step.
     *
     * Called by the orchestrator when a later step fails after this step
     * has already been executed successfully.
     *
     * @param  array<string, mixed> $context  Shared saga context at point of failure.
     */
    public function compensate(array $context): void;

    /**
     * Return a human-readable name for this step (used in logging).
     */
    public function name(): string;
}
