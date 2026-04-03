<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;

class JournalEntry
{
    private ?int $id;
    private int $tenantId;
    private int $transactionId;
    private string $accountCode;
    private string $accountName;
    private float $debitAmount;
    private float $creditAmount;
    private ?string $description;
    private string $status;
    private ?\DateTimeInterface $postedAt;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $transactionId,
        string $accountCode,
        string $accountName,
        float $debitAmount = 0.0,
        float $creditAmount = 0.0,
        ?string $description = null,
        string $status = TransactionStatus::DRAFT,
        ?\DateTimeInterface $postedAt = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id            = $id;
        $this->tenantId      = $tenantId;
        $this->transactionId = $transactionId;
        $this->accountCode   = $accountCode;
        $this->accountName   = $accountName;
        $this->debitAmount   = $debitAmount;
        $this->creditAmount  = $creditAmount;
        $this->description   = $description;
        $this->status        = $status;
        $this->postedAt      = $postedAt;
        $this->metadata      = $metadata ?? new Metadata([]);
        $this->createdAt     = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt     = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getTransactionId(): int { return $this->transactionId; }
    public function getAccountCode(): string { return $this->accountCode; }
    public function getAccountName(): string { return $this->accountName; }
    public function getDebitAmount(): float { return $this->debitAmount; }
    public function getCreditAmount(): float { return $this->creditAmount; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getPostedAt(): ?\DateTimeInterface { return $this->postedAt; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function post(): void
    {
        $this->status    = TransactionStatus::POSTED;
        $this->postedAt  = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getNetAmount(): float
    {
        return $this->debitAmount - $this->creditAmount;
    }

    public function isDebit(): bool
    {
        return $this->debitAmount > $this->creditAmount;
    }

    public function updateDetails(
        string $accountCode,
        string $accountName,
        float $debitAmount,
        float $creditAmount,
        ?string $description,
        ?Metadata $metadata,
    ): void {
        $this->accountCode  = $accountCode;
        $this->accountName  = $accountName;
        $this->debitAmount  = $debitAmount;
        $this->creditAmount = $creditAmount;
        $this->description  = $description;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
