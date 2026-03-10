<?php

declare(strict_types=1);

namespace App\Domain\Order\Saga;

/**
 * SagaStepInterface
 *
 * A single, atomic step within a saga.
 */
interface SagaStepInterface
{
    /**
     * Human-readable step name for logging and status reporting.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Forward action.  May mutate $context to pass data to subsequent steps.
     *
     * @param  array<string, mixed> &$context
     * @return void
     *
     * @throws \Throwable  Any exception triggers compensating transactions.
     */
    public function execute(array &$context): void;

    /**
     * Compensating action (rollback).  Must be idempotent.
     *
     * @param  array<string, mixed> &$context
     * @return void
     */
    public function compensate(array &$context): void;
}
