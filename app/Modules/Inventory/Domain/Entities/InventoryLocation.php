<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class InventoryLocation
{
    private ?int $id;
    private int $tenantId;
    private int $warehouseId;
    private ?int $zoneId;
    private ?string $code;
    private string $name;
    private string $type;
    private ?string $aisle;
    private ?string $row;
    private ?string $level;
    private ?string $bin;
    private ?float $capacity;
    private ?float $weightLimit;
    private ?string $barcode;
    private ?string $qrCode;
    private bool $isPickable;
    private bool $isStorable;
    private bool $isPacking;
    private bool $isActive;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $warehouseId,
        string $name,
        string $type,
        ?int $zoneId = null,
        ?string $code = null,
        ?string $aisle = null,
        ?string $row = null,
        ?string $level = null,
        ?string $bin = null,
        ?float $capacity = null,
        ?float $weightLimit = null,
        ?string $barcode = null,
        ?string $qrCode = null,
        bool $isPickable = true,
        bool $isStorable = true,
        bool $isPacking = false,
        bool $isActive = true,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id          = $id;
        $this->tenantId    = $tenantId;
        $this->warehouseId = $warehouseId;
        $this->zoneId      = $zoneId;
        $this->code        = $code;
        $this->name        = $name;
        $this->type        = $type;
        $this->aisle       = $aisle;
        $this->row         = $row;
        $this->level       = $level;
        $this->bin         = $bin;
        $this->capacity    = $capacity;
        $this->weightLimit = $weightLimit;
        $this->barcode     = $barcode;
        $this->qrCode      = $qrCode;
        $this->isPickable  = $isPickable;
        $this->isStorable  = $isStorable;
        $this->isPacking   = $isPacking;
        $this->isActive    = $isActive;
        $this->metadata    = $metadata ?? new Metadata([]);
        $this->createdAt   = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt   = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getZoneId(): ?int { return $this->zoneId; }
    public function getCode(): ?string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getAisle(): ?string { return $this->aisle; }
    public function getRow(): ?string { return $this->row; }
    public function getLevel(): ?string { return $this->level; }
    public function getBin(): ?string { return $this->bin; }
    public function getCapacity(): ?float { return $this->capacity; }
    public function getWeightLimit(): ?float { return $this->weightLimit; }
    public function getBarcode(): ?string { return $this->barcode; }
    public function getQrCode(): ?string { return $this->qrCode; }
    public function isPickable(): bool { return $this->isPickable; }
    public function isStorable(): bool { return $this->isStorable; }
    public function isPacking(): bool { return $this->isPacking; }
    public function isActive(): bool { return $this->isActive; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        int $warehouseId,
        string $name,
        string $type,
        ?int $zoneId,
        ?string $code,
        ?string $aisle,
        ?string $row,
        ?string $level,
        ?string $bin,
        ?float $capacity,
        ?float $weightLimit,
        ?string $barcode,
        ?string $qrCode,
        bool $isPickable,
        bool $isStorable,
        bool $isPacking,
        bool $isActive,
        ?Metadata $metadata
    ): void {
        $this->warehouseId = $warehouseId;
        $this->name        = $name;
        $this->type        = $type;
        $this->zoneId      = $zoneId;
        $this->code        = $code;
        $this->aisle       = $aisle;
        $this->row         = $row;
        $this->level       = $level;
        $this->bin         = $bin;
        $this->capacity    = $capacity;
        $this->weightLimit = $weightLimit;
        $this->barcode     = $barcode;
        $this->qrCode      = $qrCode;
        $this->isPickable  = $isPickable;
        $this->isStorable  = $isStorable;
        $this->isPacking   = $isPacking;
        $this->isActive    = $isActive;
        $this->metadata    = $metadata ?? new Metadata([]);
        $this->updatedAt   = new \DateTimeImmutable;
    }

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
}
