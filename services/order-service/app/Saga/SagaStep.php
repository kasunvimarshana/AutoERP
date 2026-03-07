<?php

namespace App\Saga;

/**
 * Abstract base for every step in the Order Placement Saga.
 *
 * Each concrete step is responsible for:
 *  - execute()    – performing its forward action (local or via RabbitMQ)
 *  - compensate() – rolling back its action if a later step fails
 */
abstract class SagaStep
{
    /**
     * Execute the forward action for this step.
     *
     * @param  array<string, mixed>  $payload  Context data for this step
     */
    abstract public function execute(array $payload): SagaStepResult;

    /**
     * Compensate (undo) the forward action for this step.
     *
     * @param  array<string, mixed>  $payload  Context data including step result
     */
    abstract public function compensate(array $payload): SagaStepResult;

    /**
     * Human-readable name identifying this step.
     */
    abstract public function name(): string;
}
