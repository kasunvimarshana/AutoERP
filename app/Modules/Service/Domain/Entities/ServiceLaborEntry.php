<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServiceLaborEntry
{
    private const VALID_STATUSES = ['draft', 'approved', 'posted'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceWorkOrderId,
        private readonly int $employeeId,
        private ?int $orgUnitId = null,
        private ?int $serviceTaskId = null,
        private ?string $startedAt = null,
        private ?string $endedAt = null,
        private float $hoursWorked = 0.0,
        private float $laborRate = 0.0,
        private float $laborAmount = 0.0,
        private float $commissionRate = 0.0,
        private float $commissionAmount = 0.0,
        private float $incentiveAmount = 0.0,
        private string $status = 'draft',
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid labor entry status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getServiceWorkOrderId(): int { return $this->serviceWorkOrderId; }
    public function getServiceTaskId(): ?int { return $this->serviceTaskId; }
    public function getEmployeeId(): int { return $this->employeeId; }
    public function getStartedAt(): ?string { return $this->startedAt; }
    public function getEndedAt(): ?string { return $this->endedAt; }
    public function getHoursWorked(): float { return $this->hoursWorked; }
    public function getLaborRate(): float { return $this->laborRate; }
    public function getLaborAmount(): float { return $this->laborAmount; }
    public function getCommissionRate(): float { return $this->commissionRate; }
    public function getCommissionAmount(): float { return $this->commissionAmount; }
    public function getIncentiveAmount(): float { return $this->incentiveAmount; }
    public function getStatus(): string { return $this->status; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
