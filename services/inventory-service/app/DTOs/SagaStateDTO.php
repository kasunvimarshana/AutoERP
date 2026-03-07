<?php

namespace App\DTOs;

class SagaStateDTO
{
    /** Saga step states */
    const STATUS_PENDING     = 'pending';
    const STATUS_RUNNING     = 'running';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_FAILED      = 'failed';
    const STATUS_COMPENSATING = 'compensating';
    const STATUS_COMPENSATED = 'compensated';

    private array $steps = [];

    public function __construct(
        public readonly string  $sagaId,
        public readonly string  $sagaType,
        public readonly int     $tenantId,
        private string          $status = self::STATUS_PENDING,
        private ?string         $failedStep = null,
        private ?string         $errorMessage = null,
        public readonly array   $context = [],
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Step Management
    |--------------------------------------------------------------------------
    */

    public function addStep(string $name, string $status = self::STATUS_PENDING): self
    {
        $this->steps[$name] = [
            'name'       => $name,
            'status'     => $status,
            'started_at' => null,
            'ended_at'   => null,
        ];

        return $this;
    }

    public function startStep(string $name): self
    {
        $this->steps[$name]['status']     = self::STATUS_RUNNING;
        $this->steps[$name]['started_at'] = now()->toIso8601String();
        $this->status                     = self::STATUS_RUNNING;

        return $this;
    }

    public function completeStep(string $name): self
    {
        $this->steps[$name]['status']   = self::STATUS_COMPLETED;
        $this->steps[$name]['ended_at'] = now()->toIso8601String();

        return $this;
    }

    public function failStep(string $name, string $error): self
    {
        $this->steps[$name]['status']   = self::STATUS_FAILED;
        $this->steps[$name]['ended_at'] = now()->toIso8601String();
        $this->status                   = self::STATUS_FAILED;
        $this->failedStep               = $name;
        $this->errorMessage             = $error;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Top-level Status
    |--------------------------------------------------------------------------
    */

    public function complete(): self
    {
        $this->status = self::STATUS_COMPLETED;

        return $this;
    }

    public function compensate(): self
    {
        $this->status = self::STATUS_COMPENSATING;

        return $this;
    }

    public function compensated(): self
    {
        $this->status = self::STATUS_COMPENSATED;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFailedStep(): ?string
    {
        return $this->failedStep;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /*
    |--------------------------------------------------------------------------
    | Serialisation
    |--------------------------------------------------------------------------
    */

    public function toArray(): array
    {
        return [
            'saga_id'       => $this->sagaId,
            'saga_type'     => $this->sagaType,
            'tenant_id'     => $this->tenantId,
            'status'        => $this->status,
            'failed_step'   => $this->failedStep,
            'error_message' => $this->errorMessage,
            'steps'         => $this->steps,
            'context'       => $this->context,
        ];
    }
}
