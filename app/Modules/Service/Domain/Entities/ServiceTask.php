<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServiceTask
{
    private const VALID_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceWorkOrderId,
        private readonly string $description,
        private ?int $orgUnitId = null,
        private ?string $taskCode = null,
        private int $lineNumber = 1,
        private string $status = 'pending',
        private ?int $assignedEmployeeId = null,
        private float $estimatedHours = 0.0,
        private float $actualHours = 0.0,
        private float $laborRate = 0.0,
        private float $laborAmount = 0.0,
        private float $commissionAmount = 0.0,
        private float $incentiveAmount = 0.0,
        private ?string $completedAt = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid service task status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getServiceWorkOrderId(): int { return $this->serviceWorkOrderId; }
    public function getLineNumber(): int { return $this->lineNumber; }
    public function getTaskCode(): ?string { return $this->taskCode; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getAssignedEmployeeId(): ?int { return $this->assignedEmployeeId; }
    public function getEstimatedHours(): float { return $this->estimatedHours; }
    public function getActualHours(): float { return $this->actualHours; }
    public function getLaborRate(): float { return $this->laborRate; }
    public function getLaborAmount(): float { return $this->laborAmount; }
    public function getCommissionAmount(): float { return $this->commissionAmount; }
    public function getIncentiveAmount(): float { return $this->incentiveAmount; }
    public function getCompletedAt(): ?string { return $this->completedAt; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
