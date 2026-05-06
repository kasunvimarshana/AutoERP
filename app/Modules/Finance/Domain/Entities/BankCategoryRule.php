<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class BankCategoryRule
{
    public function __construct(
        private int $tenantId,
        private string $name,
        private array $conditions,
        private int $accountId,
        private ?int $bankAccountId = null,
        private int $priority = 0,
        private ?string $descriptionTemplate = null,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getBankAccountId(): ?int
    {
        return $this->bankAccountId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDescriptionTemplate(): ?string
    {
        return $this->descriptionTemplate;
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
        array $conditions,
        int $accountId,
        ?int $bankAccountId,
        int $priority,
        ?string $descriptionTemplate,
        bool $isActive,
    ): void {
        $this->name = $name;
        $this->conditions = $conditions;
        $this->accountId = $accountId;
        $this->bankAccountId = $bankAccountId;
        $this->priority = $priority;
        $this->descriptionTemplate = $descriptionTemplate;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
