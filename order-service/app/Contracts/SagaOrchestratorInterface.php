<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Saga\SagaContext;

/**
 * Contract for the Saga orchestrator.
 *
 * The orchestrator manages the complete lifecycle of a distributed
 * transaction: executes steps in order, tracks state in the saga log,
 * and triggers compensations in LIFO order when any step fails.
 *
 * Orchestration-based Saga (vs. Choreography) gives us a single
 * place to reason about and debug the full transaction flow.
 */
interface SagaOrchestratorInterface
{
    /**
     * Register a Saga step to be executed in sequence.
     *
     * Returns $this for a fluent builder interface.
     */
    public function addStep(SagaStepInterface $step): static;

    /**
     * Execute all registered steps in order.
     *
     * On failure, executes compensations in reverse order (LIFO)
     * for every step that already completed successfully.
     *
     * @throws \App\Exceptions\SagaException  If compensation itself fails.
     */
    public function execute(SagaContext $context): void;
}
