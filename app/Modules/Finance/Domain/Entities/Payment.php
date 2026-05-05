<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class Payment
{
    public function __construct(
        private int $tenantId,
        private string $paymentNumber,
        private string $direction,
        private string $partyType,
        private int $partyId,
        private int $paymentMethodId,
        private int $accountId,
        private float $amount,
        private int $currencyId,
        private \DateTimeInterface $paymentDate,
        private float $exchangeRate = 1.0,
        private float $baseAmount = 0.0,
        private string $status = 'draft',
        private ?string $reference = null,
        private ?string $notes = null,
        private ?string $idempotencyKey = null,
        private ?int $journalEntryId = null,
        private int $rowVersion = 1,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
        if (abs($this->baseAmount) < PHP_FLOAT_EPSILON) {
            $this->baseAmount = $this->amount * $this->exchangeRate;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getPaymentNumber(): string
    {
        return $this->paymentNumber;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getPartyType(): string
    {
        return $this->partyType;
    }

    public function getPartyId(): int
    {
        return $this->partyId;
    }

    public function getPaymentMethodId(): int
    {
        return $this->paymentMethodId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): float
    {
        return $this->exchangeRate;
    }

    public function getBaseAmount(): float
    {
        return $this->baseAmount;
    }

    public function getPaymentDate(): \DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function post(?int $journalEntryId = null): void
    {
        $this->status = 'posted';
        $this->journalEntryId = $journalEntryId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function void(): void
    {
        $this->status = 'voided';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function update(
        int $paymentMethodId,
        int $accountId,
        float $amount,
        int $currencyId,
        float $exchangeRate,
        \DateTimeInterface $paymentDate,
        ?string $reference,
        ?string $notes,
    ): void {
        $this->paymentMethodId = $paymentMethodId;
        $this->accountId = $accountId;
        $this->amount = $amount;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->baseAmount = $amount * $exchangeRate;
        $this->paymentDate = $paymentDate;
        $this->reference = $reference;
        $this->notes = $notes;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
