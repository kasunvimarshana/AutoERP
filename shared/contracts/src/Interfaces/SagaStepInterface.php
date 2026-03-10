<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Interfaces;

/**
 * SagaStepInterface
 *
 * A single, atomic step within a saga.  Each step provides both
 * the forward action and its compensating (rollback) action.
 */
interface SagaStepInterface
{
    /**
     * A human-readable name for this step, used in logs and status reporting.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Execute the forward action of this step.
     *
     * The step may mutate $context to pass data to subsequent steps.
     *
     * @param  array<string, mixed> &$context
     * @return void
     *
     * @throws \Throwable  Any exception signals that compensation should start.
     */
    public function execute(array &$context): void;

    /**
     * Execute the compensating action for this step.
     *
     * Must be idempotent — it may be called multiple times.
     *
     * @param  array<string, mixed> &$context
     * @return void
     */
    public function compensate(array &$context): void;
}
