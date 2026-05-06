<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class PaymentMethod
{
    public function __construct(
        private int $tenantId,
        private string $name,
        private string $type = 'bank_transfer',
        private ?int $accountId = null,
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
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

    public function update(string $name, string $type, ?int $accountId, bool $isActive): void
    {
        $this->name = $name;
        $this->type = $type;
        $this->accountId = $accountId;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
