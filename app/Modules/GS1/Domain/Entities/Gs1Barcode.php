<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class Gs1Barcode
{
    private ?int $id;
    private int $tenantId;
    private int $gs1IdentifierId;
    private string $barcodeType;
    private string $barcodeData;
    private ?string $applicationIdentifiers;
    private bool $isPrimary;
    private bool $isActive;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $gs1IdentifierId,
        string $barcodeType,
        string $barcodeData,
        ?string $applicationIdentifiers = null,
        bool $isPrimary = false,
        bool $isActive = true,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                     = $id;
        $this->tenantId               = $tenantId;
        $this->gs1IdentifierId        = $gs1IdentifierId;
        $this->barcodeType            = $barcodeType;
        $this->barcodeData            = $barcodeData;
        $this->applicationIdentifiers = $applicationIdentifiers;
        $this->isPrimary              = $isPrimary;
        $this->isActive               = $isActive;
        $this->metadata               = $metadata ?? new Metadata([]);
        $this->createdAt              = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt              = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getGs1IdentifierId(): int { return $this->gs1IdentifierId; }
    public function getBarcodeType(): string { return $this->barcodeType; }
    public function getBarcodeData(): string { return $this->barcodeData; }
    public function getApplicationIdentifiers(): ?string { return $this->applicationIdentifiers; }
    public function isPrimary(): bool { return $this->isPrimary; }
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

    public function setPrimary(): void
    {
        $this->isPrimary = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $barcodeType,
        string $barcodeData,
        ?string $applicationIdentifiers,
        bool $isPrimary,
        bool $isActive,
        ?Metadata $metadata,
    ): void {
        $this->barcodeType            = $barcodeType;
        $this->barcodeData            = $barcodeData;
        $this->applicationIdentifiers = $applicationIdentifiers;
        $this->isPrimary              = $isPrimary;
        $this->isActive               = $isActive;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
