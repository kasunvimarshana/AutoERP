<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

class Refund
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $originalPaymentId,
        private float $amount,
        private string $currency,
        private string $status,  // pending|completed|failed
        private ?string $reason,
        private ?string $reference,
        private \DateTimeInterface $refundDate,
        private ?int $journalEntryId,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOriginalPaymentId(): int { return $this->originalPaymentId; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getReason(): ?string { return $this->reason; }
    public function getReference(): ?string { return $this->reference; }
    public function getRefundDate(): \DateTimeInterface { return $this->refundDate; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function complete(): void { $this->status = 'completed'; }
    public function fail(): void { $this->status = 'failed'; }
}
