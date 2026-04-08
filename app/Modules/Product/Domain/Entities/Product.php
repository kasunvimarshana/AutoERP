<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeInterface;

class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $categoryId,
        public readonly string $name,
        public readonly string $sku,
        public readonly ?string $barcode,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $description,
        public readonly ?string $shortDescription,
        public readonly string $unit,
        public readonly ?float $weight,
        public readonly ?string $weightUnit,
        public readonly bool $hasVariants,
        public readonly bool $isTrackable,
        public readonly bool $isSerialTracked,
        public readonly bool $isBatchTracked,
        public readonly float $costPrice,
        public readonly float $salePrice,
        public readonly float $minStockLevel,
        public readonly float $reorderPoint,
        public readonly ?string $taxGroupId,
        public readonly ?string $imageUrl,
        public readonly array $metadata,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isPhysical(): bool { return $this->type === 'physical'; }
    public function isService(): bool { return $this->type === 'service'; }
    public function isDigital(): bool { return $this->type === 'digital'; }
    public function isCombo(): bool { return $this->type === 'combo'; }
    public function isVariable(): bool { return $this->type === 'variable'; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function needsInventoryTracking(): bool
    {
        return $this->isTrackable && ($this->isPhysical() || $this->isCombo());
    }
}
