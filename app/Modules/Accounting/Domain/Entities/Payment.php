<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

class Payment
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $payableType,
        private int $payableId,
        private float $amount,
        private string $currency,
        private string $paymentMethod,  // cash|bank_transfer|credit_card|cheque|other
        private string $status,          // pending|completed|failed|cancelled
        private string $direction,       // inbound|outbound
        private ?string $reference,
        private ?string $notes,
        private \DateTimeInterface $paymentDate,
        private ?int $journalEntryId,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getPayableType(): string { return $this->payableType; }
    public function getPayableId(): int { return $this->payableId; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getStatus(): string { return $this->status; }
    public function getDirection(): string { return $this->direction; }
    public function getReference(): ?string { return $this->reference; }
    public function getNotes(): ?string { return $this->notes; }
    public function getPaymentDate(): \DateTimeInterface { return $this->paymentDate; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function complete(): void { $this->status = 'completed'; }
    public function fail(): void { $this->status = 'failed'; }
    public function cancel(): void { $this->status = 'cancelled'; }
}
