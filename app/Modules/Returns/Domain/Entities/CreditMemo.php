<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class CreditMemo
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private ?int $stockReturnId;
    private int $partyId;
    private string $partyType;
    private string $status;
    private float $amount;
    private string $currency;
    private ?\DateTimeInterface $issueDate;
    private ?\DateTimeInterface $appliedDate;
    private ?\DateTimeInterface $voidedDate;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $partyId,
        string $partyType,
        ?int $stockReturnId = null,
        float $amount = 0.0,
        string $currency = 'USD',
        ?\DateTimeInterface $issueDate = null,
        ?\DateTimeInterface $appliedDate = null,
        ?\DateTimeInterface $voidedDate = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'draft',
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->referenceNumber = $referenceNumber;
        $this->stockReturnId   = $stockReturnId;
        $this->partyId         = $partyId;
        $this->partyType       = $partyType;
        $this->status          = $status;
        $this->amount          = $amount;
        $this->currency        = $currency;
        $this->issueDate       = $issueDate;
        $this->appliedDate     = $appliedDate;
        $this->voidedDate      = $voidedDate;
        $this->notes           = $notes;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getStockReturnId(): ?int { return $this->stockReturnId; }
    public function getPartyId(): int { return $this->partyId; }
    public function getPartyType(): string { return $this->partyType; }
    public function getStatus(): string { return $this->status; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getIssueDate(): ?\DateTimeInterface { return $this->issueDate; }
    public function getAppliedDate(): ?\DateTimeInterface { return $this->appliedDate; }
    public function getVoidedDate(): ?\DateTimeInterface { return $this->voidedDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function issue(): void
    {
        $this->status    = 'issued';
        $this->issueDate = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function apply(): void
    {
        $this->status      = 'applied';
        $this->appliedDate = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function void(): void
    {
        $this->status     = 'voided';
        $this->voidedDate = new \DateTimeImmutable;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function updateDetails(?string $notes, ?array $metadataArray): void
    {
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isIssued(): bool { return $this->status === 'issued'; }
    public function isApplied(): bool { return $this->status === 'applied'; }
    public function isVoided(): bool { return $this->status === 'voided'; }
}
