<?php

namespace Shared\Saga;

interface SagaOrchestratorInterface
{
    public function start(string $sagaType, array $payload): void;
    public function handleStepCompletion(string $sagaId, string $step, array $result): void;
    public function handleStepFailure(string $sagaId, string $step, string $error): void;
}

interface SagaStepInterface
{
    public function execute(array $payload): void;
    public function compensate(array $payload): void;
}

abstract class AbstractSaga
{
    protected string $id;
    protected array $steps = [];
    protected int $currentStep = 0;
    protected array $payload = [];

    public function __construct(string $id, array $payload)
    {
        $this->id = $id;
        $this->payload = $payload;
    }

    abstract public function defineSteps(): void;
    
    public function getId(): string { return $this->id; }
    public function getPayload(): array { return $this->payload; }
}
