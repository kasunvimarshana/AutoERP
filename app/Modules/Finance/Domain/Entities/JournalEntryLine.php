<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class JournalEntryLine
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        private int $accountId,
        private float $debitAmount = 0.0,
        private float $creditAmount = 0.0,
        private ?string $description = null,
        private ?int $currencyId = null,
        private float $exchangeRate = 1.0,
        private float $baseDebitAmount = 0.0,
        private float $baseCreditAmount = 0.0,
        private ?int $costCenterId = null,
        private ?array $metadata = null,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDebitAmount(): float
    {
        return $this->debitAmount;
    }

    public function getCreditAmount(): float
    {
        return $this->creditAmount;
    }

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): float
    {
        return $this->exchangeRate;
    }

    public function getBaseDebitAmount(): float
    {
        return $this->baseDebitAmount;
    }

    public function getBaseCreditAmount(): float
    {
        return $this->baseCreditAmount;
    }

    public function getCostCenterId(): ?int
    {
        return $this->costCenterId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
