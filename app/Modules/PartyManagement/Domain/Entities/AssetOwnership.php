<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Domain\Entities;

use DateTimeImmutable;

class AssetOwnership
{
    public function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly string $partyId,
        private readonly string $assetId,
        private readonly string $ownershipType,
        private readonly DateTimeImmutable $acquisitionDate,
        private ?DateTimeImmutable $disposalDate,
        private readonly string $acquisitionCost,
        private ?string $notes,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getOwnershipType(): string
    {
        return $this->ownershipType;
    }

    public function getAcquisitionDate(): DateTimeImmutable
    {
        return $this->acquisitionDate;
    }

    public function getDisposalDate(): ?DateTimeImmutable
    {
        return $this->disposalDate;
    }

    public function getAcquisitionCost(): string
    {
        return $this->acquisitionCost;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isActive(): bool
    {
        return $this->disposalDate === null;
    }

    public function dispose(DateTimeImmutable $disposalDate): void
    {
        $this->disposalDate = $disposalDate;
    }
}
