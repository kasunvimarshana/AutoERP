<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class StockReturn
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $returnType;
    private string $status;
    private int $partyId;
    private string $partyType;
    private ?int $originalReferenceId;
    private ?string $originalReferenceType;
    private ?string $returnReason;
    private float $totalAmount;
    private string $currency;
    private bool $restock;
    private ?int $restockLocationId;
    private float $restockingFee;
    private bool $creditMemoIssued;
    private ?string $creditMemoReference;
    private ?int $approvedBy;
    private ?\DateTimeInterface $approvedAt;
    private ?int $processedBy;
    private ?\DateTimeInterface $processedAt;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        string $returnType,
        int $partyId,
        string $partyType,
        ?int $originalReferenceId = null,
        ?string $originalReferenceType = null,
        ?string $returnReason = null,
        float $totalAmount = 0.0,
        string $currency = 'USD',
        bool $restock = true,
        ?int $restockLocationId = null,
        float $restockingFee = 0.0,
        bool $creditMemoIssued = false,
        ?string $creditMemoReference = null,
        ?int $approvedBy = null,
        ?\DateTimeInterface $approvedAt = null,
        ?int $processedBy = null,
        ?\DateTimeInterface $processedAt = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'draft',
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                    = $id;
        $this->tenantId              = $tenantId;
        $this->referenceNumber       = $referenceNumber;
        $this->returnType            = $returnType;
        $this->status                = $status;
        $this->partyId               = $partyId;
        $this->partyType             = $partyType;
        $this->originalReferenceId   = $originalReferenceId;
        $this->originalReferenceType = $originalReferenceType;
        $this->returnReason          = $returnReason;
        $this->totalAmount           = $totalAmount;
        $this->currency              = $currency;
        $this->restock               = $restock;
        $this->restockLocationId     = $restockLocationId;
        $this->restockingFee         = $restockingFee;
        $this->creditMemoIssued      = $creditMemoIssued;
        $this->creditMemoReference   = $creditMemoReference;
        $this->approvedBy            = $approvedBy;
        $this->approvedAt            = $approvedAt;
        $this->processedBy           = $processedBy;
        $this->processedAt           = $processedAt;
        $this->notes                 = $notes;
        $this->metadata              = $metadata ?? new Metadata([]);
        $this->createdAt             = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt             = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getReturnType(): string { return $this->returnType; }
    public function getStatus(): string { return $this->status; }
    public function getPartyId(): int { return $this->partyId; }
    public function getPartyType(): string { return $this->partyType; }
    public function getOriginalReferenceId(): ?int { return $this->originalReferenceId; }
    public function getOriginalReferenceType(): ?string { return $this->originalReferenceType; }
    public function getReturnReason(): ?string { return $this->returnReason; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getRestock(): bool { return $this->restock; }
    public function getRestockLocationId(): ?int { return $this->restockLocationId; }
    public function getRestockingFee(): float { return $this->restockingFee; }
    public function getCreditMemoIssued(): bool { return $this->creditMemoIssued; }
    public function getCreditMemoReference(): ?string { return $this->creditMemoReference; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function getProcessedBy(): ?int { return $this->processedBy; }
    public function getProcessedAt(): ?\DateTimeInterface { return $this->processedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function approve(int $approvedBy): void
    {
        $this->status     = 'approved';
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function reject(): void
    {
        $this->status    = 'rejected';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function complete(int $processedBy): void
    {
        $this->status      = 'completed';
        $this->processedBy = $processedBy;
        $this->processedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function issueCreditMemo(string $reference): void
    {
        $this->creditMemoIssued    = true;
        $this->creditMemoReference = $reference;
        $this->updatedAt           = new \DateTimeImmutable;
    }

    public function isPurchaseReturn(): bool { return $this->returnType === 'purchase_return'; }
    public function isSalesReturn(): bool { return $this->returnType === 'sales_return'; }

    public function updateDetails(?string $notes, ?array $metadataArray, ?string $returnReason): void
    {
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        if ($returnReason !== null) { $this->returnReason = $returnReason; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
