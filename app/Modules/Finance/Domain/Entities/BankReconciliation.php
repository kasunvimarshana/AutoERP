<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class BankReconciliation
{
    public function __construct(
        private int $tenantId,
        private int $bankAccountId,
        private \DateTimeInterface $periodStart,
        private \DateTimeInterface $periodEnd,
        private float $openingBalance,
        private float $closingBalance,
        private string $status = 'draft',
        private ?int $completedBy = null,
        private ?\DateTimeInterface $completedAt = null,
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

    public function getBankAccountId(): int
    {
        return $this->bankAccountId;
    }

    public function getPeriodStart(): \DateTimeInterface
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function getOpeningBalance(): float
    {
        return $this->openingBalance;
    }

    public function getClosingBalance(): float
    {
        return $this->closingBalance;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCompletedBy(): ?int
    {
        return $this->completedBy;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
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

    public function complete(int $completedBy): void
    {
        $this->status = 'completed';
        $this->completedBy = $completedBy;
        $this->completedAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateBalances(float $openingBalance, float $closingBalance): void
    {
        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
