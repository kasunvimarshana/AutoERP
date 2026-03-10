<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Interfaces;

/**
 * SagaOrchestratorInterface
 *
 * Coordinates multi-step distributed transactions following the Saga pattern.
 * Each saga consists of a sequence of steps; if any step fails its compensating
 * action (rollback) is invoked for all previously completed steps in reverse order.
 */
interface SagaOrchestratorInterface
{
    /**
     * Execute the saga.
     *
     * The orchestrator iterates through the registered steps in order.
     * On success of all steps it commits the saga.
     * On any failure it triggers compensating transactions in reverse order.
     *
     * @param  string               $sagaId   Unique correlation identifier
     * @param  array<string, mixed> $context  Shared data passed between steps
     * @return array<string, mixed>            Final enriched context
     *
     * @throws \KvSaas\Contracts\Exceptions\SagaFailedException
     */
    public function execute(string $sagaId, array $context): array;

    /**
     * Register a saga step.
     *
     * @param  SagaStepInterface $step
     * @return static
     */
    public function addStep(SagaStepInterface $step): static;

    /**
     * Return the current status of a saga instance.
     *
     * @param  string $sagaId
     * @return array{status: string, current_step: string|null, context: array<string, mixed>}
     */
    public function getStatus(string $sagaId): array;
}
