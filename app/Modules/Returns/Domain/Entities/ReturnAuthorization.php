<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class ReturnAuthorization
{
    private ?int $id;
    private int $tenantId;
    private string $rmaNumber;
    private string $returnType;
    private int $partyId;
    private string $partyType;
    private ?string $reason;
    private string $status;
    private ?int $authorizedBy;
    private ?\DateTimeInterface $authorizedAt;
    private ?\DateTimeInterface $expiresAt;
    private ?\DateTimeInterface $cancelledAt;
    private ?int $stockReturnId;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $rmaNumber,
        string $returnType,
        int $partyId,
        string $partyType,
        ?string $reason = null,
        string $status = 'pending',
        ?int $authorizedBy = null,
        ?\DateTimeInterface $authorizedAt = null,
        ?\DateTimeInterface $expiresAt = null,
        ?\DateTimeInterface $cancelledAt = null,
        ?int $stockReturnId = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id           = $id;
        $this->tenantId     = $tenantId;
        $this->rmaNumber    = $rmaNumber;
        $this->returnType   = $returnType;
        $this->partyId      = $partyId;
        $this->partyType    = $partyType;
        $this->reason       = $reason;
        $this->status       = $status;
        $this->authorizedBy = $authorizedBy;
        $this->authorizedAt = $authorizedAt;
        $this->expiresAt    = $expiresAt;
        $this->cancelledAt  = $cancelledAt;
        $this->stockReturnId = $stockReturnId;
        $this->notes        = $notes;
        $this->metadata     = $metadata ?? new Metadata([]);
        $this->createdAt    = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt    = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getRmaNumber(): string { return $this->rmaNumber; }
    public function getReturnType(): string { return $this->returnType; }
    public function getPartyId(): int { return $this->partyId; }
    public function getPartyType(): string { return $this->partyType; }
    public function getReason(): ?string { return $this->reason; }
    public function getStatus(): string { return $this->status; }
    public function getAuthorizedBy(): ?int { return $this->authorizedBy; }
    public function getAuthorizedAt(): ?\DateTimeInterface { return $this->authorizedAt; }
    public function getExpiresAt(): ?\DateTimeInterface { return $this->expiresAt; }
    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function getStockReturnId(): ?int { return $this->stockReturnId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function approve(int $authorizedBy, ?\DateTimeInterface $expiresAt = null): void
    {
        $this->status       = 'approved';
        $this->authorizedBy = $authorizedBy;
        $this->authorizedAt = new \DateTimeImmutable;
        $this->expiresAt    = $expiresAt;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status      = 'cancelled';
        $this->cancelledAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function expire(): void
    {
        $this->status    = 'expired';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function linkToReturn(int $stockReturnId): void
    {
        $this->stockReturnId = $stockReturnId;
        $this->updatedAt     = new \DateTimeImmutable;
    }

    public function updateDetails(?string $reason, ?string $notes, ?array $metadataArray): void
    {
        if ($reason !== null) { $this->reason = $reason; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isExpired(): bool { return $this->status === 'expired'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
}
