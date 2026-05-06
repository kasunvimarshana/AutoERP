<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class LeavePolicy
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $leaveTypeId,
        private string $name,
        private string $accrualType,
        private float $accrualAmount,
        private ?int $orgUnitId,
        private bool $isActive,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private int $rowVersion = 1,
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

    public function getLeaveTypeId(): int
    {
        return $this->leaveTypeId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccrualType(): string
    {
        return $this->accrualType;
    }

    public function getAccrualAmount(): float
    {
        return $this->accrualAmount;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }
}
