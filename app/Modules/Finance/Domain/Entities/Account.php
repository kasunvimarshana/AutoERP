<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class Account
{
    public function __construct(
        private int $tenantId,
        private string $code,
        private string $name,
        private string $type,
        private string $normalBalance,
        private ?int $parentId = null,
        private ?string $subType = null,
        private bool $isSystem = false,
        private bool $isBankAccount = false,
        private bool $isCreditCard = false,
        private ?int $currencyId = null,
        private ?string $description = null,
        private bool $isActive = true,
        private ?string $path = null,
        private int $depth = 0,
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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }

    public function getNormalBalance(): string
    {
        return $this->normalBalance;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function isBankAccount(): bool
    {
        return $this->isBankAccount;
    }

    public function isCreditCard(): bool
    {
        return $this->isCreditCard;
    }

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
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
        string $code,
        string $name,
        string $type,
        string $normalBalance,
        ?int $parentId,
        ?string $subType,
        bool $isSystem,
        bool $isBankAccount,
        bool $isCreditCard,
        ?int $currencyId,
        ?string $description,
        bool $isActive,
    ): void {
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->normalBalance = $normalBalance;
        $this->parentId = $parentId;
        $this->subType = $subType;
        $this->isSystem = $isSystem;
        $this->isBankAccount = $isBankAccount;
        $this->isCreditCard = $isCreditCard;
        $this->currencyId = $currencyId;
        $this->description = $description;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setHierarchy(?string $path, int $depth): void
    {
        $this->path = $path;
        $this->depth = $depth;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
