<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class LeaveBalance
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private readonly int $leaveTypeId,
        private readonly int $year,
        private float $allocated,
        private float $used,
        private float $pending,
        private float $carried,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getLeaveTypeId(): int
    {
        return $this->leaveTypeId;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getAllocated(): float
    {
        return $this->allocated;
    }

    public function getUsed(): float
    {
        return $this->used;
    }

    public function getPending(): float
    {
        return $this->pending;
    }

    public function getCarried(): float
    {
        return $this->carried;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getAvailable(): float
    {
        return $this->allocated + $this->carried - $this->used - $this->pending;
    }

    public function canRequest(float $days): bool
    {
        return $this->getAvailable() >= $days;
    }
}
