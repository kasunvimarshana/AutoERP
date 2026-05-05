<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class PaymentTerm
{
    public function __construct(
        private int $tenantId,
        private string $name,
        private int $days = 30,
        private bool $isDefault = false,
        private bool $isActive = true,
        private int $rowVersion = 1,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
        private ?string $description = null,
        private ?int $discountDays = null,
        private ?float $discountRate = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

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

    public function getDays(): int
    {
        return $this->days;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDiscountDays(): ?int
    {
        return $this->discountDays;
    }

    public function getDiscountRate(): ?float
    {
        return $this->discountRate;
    }

    public function update(
        string $name,
        int $days,
        bool $isDefault,
        bool $isActive,
        ?string $description = null,
        ?int $discountDays = null,
        ?float $discountRate = null,
    ): void {
        $this->name = $name;
        $this->days = $days;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->discountDays = $discountDays;
        $this->discountRate = $discountRate;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
