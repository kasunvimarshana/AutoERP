<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Entities;

/**
 * Records a single barcode scan event, optionally resolving it to a known BarcodeDefinition.
 */
class BarcodeScan
{
    public function __construct(
        private readonly ?int              $id,
        private readonly int               $tenantId,
        private readonly ?int              $barcodeDefinitionId,
        private readonly string            $scannedValue,
        private readonly ?string           $resolvedType,
        private readonly ?int              $scannedByUserId,
        private readonly ?string           $deviceId,
        private readonly ?string           $locationTag,
        private readonly array             $metadata,
        private readonly \DateTimeInterface $scannedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int                       { return $this->id; }
    public function getTenantId(): int                  { return $this->tenantId; }
    public function getBarcodeDefinitionId(): ?int      { return $this->barcodeDefinitionId; }
    public function getScannedValue(): string           { return $this->scannedValue; }
    public function getResolvedType(): ?string          { return $this->resolvedType; }
    public function getScannedByUserId(): ?int          { return $this->scannedByUserId; }
    public function getDeviceId(): ?string              { return $this->deviceId; }
    public function getLocationTag(): ?string           { return $this->locationTag; }
    public function getMetadata(): array                { return $this->metadata; }
    public function getScannedAt(): \DateTimeInterface  { return $this->scannedAt; }
}
