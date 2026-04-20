<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class ApprovalWorkflowConfig
{
    public function __construct(
        private int $tenantId,
        private string $module,
        private string $entityType,
        private string $name,
        private array $steps,
        private ?float $minAmount = null,
        private ?float $maxAmount = null,
        private bool $isActive = true,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
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

    public function getModule(): string
    {
        return $this->module;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
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

    public function update(
        string $name,
        array $steps,
        ?float $minAmount,
        ?float $maxAmount,
        bool $isActive,
    ): void {
        $this->name = $name;
        $this->steps = $steps;
        $this->minAmount = $minAmount;
        $this->maxAmount = $maxAmount;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
