<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class Gs1Identifier
{
    private ?int $id;
    private int $tenantId;
    private string $identifierType;
    private string $identifierValue;
    private ?string $entityType;
    private ?int $entityId;
    private bool $isActive;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $identifierType,
        string $identifierValue,
        ?string $entityType = null,
        ?int $entityId = null,
        bool $isActive = true,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->identifierType  = $identifierType;
        $this->identifierValue = $identifierValue;
        $this->entityType      = $entityType;
        $this->entityId        = $entityId;
        $this->isActive        = $isActive;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getIdentifierType(): string { return $this->identifierType; }
    public function getIdentifierValue(): string { return $this->identifierValue; }
    public function getEntityType(): ?string { return $this->entityType; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function isActive(): bool { return $this->isActive; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $identifierType,
        string $identifierValue,
        ?string $entityType,
        ?int $entityId,
        bool $isActive,
        ?Metadata $metadata,
    ): void {
        $this->identifierType  = $identifierType;
        $this->identifierValue = $identifierValue;
        $this->entityType      = $entityType;
        $this->entityId        = $entityId;
        $this->isActive        = $isActive;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isGtin(): bool
    {
        return $this->identifierType === 'gtin';
    }

    public function isGln(): bool
    {
        return $this->identifierType === 'gln';
    }
}
