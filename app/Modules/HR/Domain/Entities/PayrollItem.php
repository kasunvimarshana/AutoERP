<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class PayrollItem
{
    public function __construct(
        private readonly int $tenantId,
        private string $name,
        private string $code,
        private string $type,
        private string $calculationType,
        private string $value,
        private bool $isActive,
        private bool $isTaxable,
        private ?int $accountId,
        private array $metadata,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCalculationType(): string
    {
        return $this->calculationType;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isTaxable(): bool
    {
        return $this->isTaxable;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
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
}
