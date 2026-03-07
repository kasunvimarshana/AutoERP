<?php

namespace App\Saga\Contracts;

interface SagaInterface
{
    /**
     * Execute the saga step forward.
     *
     * @param array $context Shared saga context.
     * @return array Updated context after execution.
     */
    public function execute(array $context): array;

    /**
     * Compensate (undo) this step on saga failure.
     *
     * @param array $context The context at the time of compensation.
     */
    public function compensate(array $context): void;

    /**
     * Return a human-readable name for this step.
     */
    public function getName(): string;
}
