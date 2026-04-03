<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;

class Transaction
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $transactionType;
    private string $status;
    private float $amount;
    private string $currencyCode;
    private float $exchangeRate;
    private \DateTimeInterface $transactionDate;
    private ?string $description;
    private ?string $referenceType;
    private ?int $referenceId;
    private ?\DateTimeInterface $postedAt;
    private ?\DateTimeInterface $voidedAt;
    private ?string $voidReason;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        string $transactionType,
        float $amount,
        \DateTimeInterface $transactionDate,
        string $status = TransactionStatus::DRAFT,
        string $currencyCode = 'USD',
        float $exchangeRate = 1.0,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?\DateTimeInterface $postedAt = null,
        ?\DateTimeInterface $voidedAt = null,
        ?string $voidReason = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->referenceNumber = $referenceNumber;
        $this->transactionType = $transactionType;
        $this->status          = $status;
        $this->amount          = $amount;
        $this->currencyCode    = $currencyCode;
        $this->exchangeRate    = $exchangeRate;
        $this->transactionDate = $transactionDate;
        $this->description     = $description;
        $this->referenceType   = $referenceType;
        $this->referenceId     = $referenceId;
        $this->postedAt        = $postedAt;
        $this->voidedAt        = $voidedAt;
        $this->voidReason      = $voidReason;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getTransactionType(): string { return $this->transactionType; }
    public function getStatus(): string { return $this->status; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function getExchangeRate(): float { return $this->exchangeRate; }
    public function getTransactionDate(): \DateTimeInterface { return $this->transactionDate; }
    public function getDescription(): ?string { return $this->description; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function getPostedAt(): ?\DateTimeInterface { return $this->postedAt; }
    public function getVoidedAt(): ?\DateTimeInterface { return $this->voidedAt; }
    public function getVoidReason(): ?string { return $this->voidReason; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function post(): void
    {
        $this->status    = TransactionStatus::POSTED;
        $this->postedAt  = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function void(string $reason): void
    {
        $this->status    = TransactionStatus::VOIDED;
        $this->voidedAt  = new \DateTimeImmutable;
        $this->voidReason = $reason;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isPosted(): bool
    {
        return $this->status === TransactionStatus::POSTED;
    }

    public function isDraft(): bool
    {
        return $this->status === TransactionStatus::DRAFT;
    }

    public function isVoided(): bool
    {
        return $this->status === TransactionStatus::VOIDED;
    }

    public function updateDetails(
        string $transactionType,
        float $amount,
        \DateTimeInterface $transactionDate,
        string $currencyCode,
        float $exchangeRate,
        ?string $description,
        ?string $referenceType,
        ?int $referenceId,
        ?Metadata $metadata,
    ): void {
        $this->transactionType = $transactionType;
        $this->amount          = $amount;
        $this->transactionDate = $transactionDate;
        $this->currencyCode    = $currencyCode;
        $this->exchangeRate    = $exchangeRate;
        $this->description     = $description;
        $this->referenceType   = $referenceType;
        $this->referenceId     = $referenceId;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
