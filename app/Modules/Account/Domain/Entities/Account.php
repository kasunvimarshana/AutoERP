<?php

declare(strict_types=1);

namespace Modules\Account\Domain\Entities;

class Account
{
    private ?int $id;
    private int $tenantId;
    private string $code;
    private string $name;
    private string $type;
    private ?string $subtype;
    private ?string $description;
    private string $currency;
    private float $balance;
    private bool $isSystem;
    private ?int $parentId;
    private string $status;
    private ?array $attributes;
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $code,
        string $name,
        string $type,
        ?string $subtype = null,
        ?string $description = null,
        string $currency = 'USD',
        float $balance = 0.0,
        bool $isSystem = false,
        ?int $parentId = null,
        string $status = 'active',
        ?array $attributes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->subtype = $subtype;
        $this->description = $description;
        $this->currency = $currency;
        $this->balance = $balance;
        $this->isSystem = $isSystem;
        $this->parentId = $parentId;
        $this->status = $status;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getSubtype(): ?string { return $this->subtype; }
    public function getDescription(): ?string { return $this->description; }
    public function getCurrency(): string { return $this->currency; }
    public function getBalance(): float { return $this->balance; }
    public function isSystem(): bool { return $this->isSystem; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getStatus(): string { return $this->status; }
    public function getAttributes(): ?array { return $this->attributes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        string $code,
        string $name,
        string $type,
        ?string $subtype,
        ?string $description,
        string $currency,
        ?int $parentId,
        ?array $attributes,
        ?array $metadata
    ): void {
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->subtype = $subtype;
        $this->description = $description;
        $this->currency = $currency;
        $this->parentId = $parentId;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function adjustBalance(float $amount): void
    {
        $this->balance += $amount;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAsset(): bool
    {
        return $this->type === 'asset';
    }

    public function isLiability(): bool
    {
        return $this->type === 'liability';
    }

    public function isEquity(): bool
    {
        return $this->type === 'equity';
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }
}
