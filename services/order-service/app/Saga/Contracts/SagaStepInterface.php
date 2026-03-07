<?php

namespace App\Saga\Contracts;

interface SagaStepInterface
{
    public function execute(array $context): array;

    public function compensate(array $context): void;

    public function getName(): string;
}
