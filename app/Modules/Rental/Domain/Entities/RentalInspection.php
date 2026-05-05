<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class RentalInspection
{
    private const VALID_TYPES = ['pickup', 'return', 'incident_followup'];
    private const VALID_STATUSES = ['draft', 'submitted', 'approved'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private readonly int $assetId,
        private string $inspectionType = 'pickup',
        private string $inspectionStatus = 'draft',
        private ?int $orgUnitId = null,
        private ?int $inspectedBy = null,
        private ?string $inspectedAt = null,
        private ?float $meterReading = null,
        private ?float $fuelLevelPercent = null,
        private ?string $damageNotes = null,
        private ?array $media = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->inspectionType, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid inspection type: {$this->inspectionType}");
        }
        if (! in_array($this->inspectionStatus, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid inspection status: {$this->inspectionStatus}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getRentalBookingId(): int { return $this->rentalBookingId; }
    public function getAssetId(): int { return $this->assetId; }
    public function getInspectionType(): string { return $this->inspectionType; }
    public function getInspectionStatus(): string { return $this->inspectionStatus; }
    public function getInspectedBy(): ?int { return $this->inspectedBy; }
    public function getInspectedAt(): ?string { return $this->inspectedAt; }
    public function getMeterReading(): ?float { return $this->meterReading; }
    public function getFuelLevelPercent(): ?float { return $this->fuelLevelPercent; }
    public function getDamageNotes(): ?string { return $this->damageNotes; }
    public function getMedia(): ?array { return $this->media; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
