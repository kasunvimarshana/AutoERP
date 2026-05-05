<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Entities;

final class ServiceTask
{
    private string $id;
    private string $serviceOrderId;
    private string $taskName;
    private ?string $description;
    private string $status;
    private string $laborCost;
    private ?int $laborMinutes;

    public function __construct(
        string $id,
        string $serviceOrderId,
        string $taskName,
        ?string $description,
        string $status,
        string $laborCost,
        ?int $laborMinutes,
    ) {
        $this->id = $id;
        $this->serviceOrderId = $serviceOrderId;
        $this->taskName = $taskName;
        $this->description = $description;
        $this->status = $status;
        $this->laborCost = $laborCost;
        $this->laborMinutes = $laborMinutes;
    }

    public function getId(): string { return $this->id; }
    public function getServiceOrderId(): string { return $this->serviceOrderId; }
    public function getTaskName(): string { return $this->taskName; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getLaborCost(): string { return $this->laborCost; }
    public function getLaborMinutes(): ?int { return $this->laborMinutes; }

    public function complete(): void
    {
        $this->status = 'completed';
    }
}
