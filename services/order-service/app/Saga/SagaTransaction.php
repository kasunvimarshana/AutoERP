<?php

namespace App\Saga;

/**
 * Lightweight value object representing the running saga state.
 * Not to be confused with App\Models\SagaTransaction (the Eloquent model).
 */
class SagaTransaction
{
    public string $id;
    public string $status;
    public array $completedSteps = [];
    public array $stepResults = [];

    public function __construct(string $id, string $status = 'started')
    {
        $this->id     = $id;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function addCompletedStep(string $stepName): void
    {
        $this->completedSteps[] = $stepName;
    }

    public function getCompletedSteps(): array
    {
        return $this->completedSteps;
    }

    public function addStepResult(string $stepName, array $result): void
    {
        $this->stepResults[$stepName] = $result;
    }

    public function getStepResults(): array
    {
        return $this->stepResults;
    }
}
