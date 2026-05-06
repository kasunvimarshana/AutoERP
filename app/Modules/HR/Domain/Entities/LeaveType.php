<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class LeaveType
{
    public function __construct(
        private readonly int $tenantId,
        private string $name,
        private string $code,
        private string $description,
        private float $maxDaysPerYear,
        private float $carryForwardDays,
        private bool $isPaid,
        private bool $requiresApproval,
        private ?string $applicableGender,
        private int $minServiceDays,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMaxDaysPerYear(): float
    {
        return $this->maxDaysPerYear;
    }

    public function getCarryForwardDays(): float
    {
        return $this->carryForwardDays;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function getApplicableGender(): ?string
    {
        return $this->applicableGender;
    }

    public function getMinServiceDays(): int
    {
        return $this->minServiceDays;
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
