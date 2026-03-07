<?php

namespace App\Saga\Contracts;

interface SagaOrchestratorInterface
{
    public function addStep(SagaStepInterface $step): static;

    public function execute(array $payload): array;

    public function reset(): static;
}
