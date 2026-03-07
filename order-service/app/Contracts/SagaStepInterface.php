<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Saga\SagaContext;

/**
 * Contract for a single step in the Saga workflow.
 *
 * Each step encapsulates one atomic operation and its
 * compensating (rollback) transaction.  This interface
 * makes it trivial to add, remove, or reorder steps
 * without touching the orchestrator.
 *
 * Pattern: Command + Compensation (Saga Compensating Transaction)
 */
interface SagaStepInterface
{
    /**
     * Execute the forward action of this Saga step.
     *
     * Should throw a SagaException on failure so the orchestrator
     * can trigger compensations for all previously completed steps.
     *
     * @throws \App\Exceptions\SagaStepException
     */
    public function execute(SagaContext $context): void;

    /**
     * Execute the compensating (undo) action for this step.
     *
     * Compensations must be idempotent – calling them multiple
     * times must produce the same result as calling them once.
     */
    public function compensate(SagaContext $context): void;

    /**
     * Human-readable name for logging and saga log entries.
     */
    public function getName(): string;
}
