<?php
namespace App\Interfaces;

interface SagaInterface
{
    public function execute(array $data): array;
    public function compensate(string $sagaId, array $completedSteps, array $context): void;
}
