<?php

namespace App\Saga\Contracts;

use App\Models\Order;

interface SagaStepInterface
{
    /** Execute the saga step. Returns context data to pass to next steps. */
    public function execute(Order $order, array $context = []): array;

    /** Compensate/rollback this step (called when a later step fails). */
    public function compensate(Order $order, array $context = []): void;

    /** Returns the name of this step for logging. */
    public function getName(): string;
}
