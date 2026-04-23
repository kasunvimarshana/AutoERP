<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class ShiftAssignment
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private readonly int $shiftId,
        private \DateTimeInterface $effectiveFrom,
        private ?\DateTimeInterface $effectiveTo,
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

    public function getShiftId(): int
    {
        return $this->shiftId;
    }

    public function getEffectiveFrom(): \DateTimeInterface
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeInterface
    {
        return $this->effectiveTo;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
