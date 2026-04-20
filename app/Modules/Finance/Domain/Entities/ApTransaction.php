<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class ApTransaction
{
    public function __construct(
        private int $tenantId,
        private int $supplierId,
        private int $accountId,
        private string $transactionType,
        private float $amount,
        private float $balanceAfter,
        private \DateTimeInterface $transactionDate,
        private int $currencyId,
        private ?string $referenceType = null,
        private ?int $referenceId = null,
        private ?\DateTimeInterface $dueDate = null,
        private bool $isReconciled = false,
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

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBalanceAfter(): float
    {
        return $this->balanceAfter;
    }

    public function getTransactionDate(): \DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getReferenceType(): ?string
    {
        return $this->referenceType;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function isReconciled(): bool
    {
        return $this->isReconciled;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function reconcile(): void
    {
        $this->isReconciled = true;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
