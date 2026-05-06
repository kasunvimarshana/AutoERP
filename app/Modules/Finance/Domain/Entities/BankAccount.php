<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class BankAccount
{
    public function __construct(
        private int $tenantId,
        private int $accountId,
        private string $name,
        private string $bankName,
        private string $accountNumber,
        private int $currencyId,
        private ?string $routingNumber = null,
        private float $currentBalance = 0.0,
        private ?\DateTimeInterface $lastSyncAt = null,
        private ?string $feedProvider = null,
        private bool $isActive = true,
        private int $rowVersion = 1,
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

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getRoutingNumber(): ?string
    {
        return $this->routingNumber;
    }

    public function getCurrentBalance(): float
    {
        return $this->currentBalance;
    }

    public function getLastSyncAt(): ?\DateTimeInterface
    {
        return $this->lastSyncAt;
    }

    public function getFeedProvider(): ?string
    {
        return $this->feedProvider;
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

    public function update(
        string $bankName,
        string $accountNumber,
        ?string $routingNumber,
        bool $isActive,
    ): void {
        $this->name = $name;
        $this->bankName = $bankName;
        $this->accountNumber = $accountNumber;
        $this->routingNumber = $routingNumber;
        $this->isActive = $isActive;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateBalance(float $balance): void
    {
        $this->currentBalance = $balance;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
