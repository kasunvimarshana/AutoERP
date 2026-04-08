<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Product\Domain\ValueObjects\ProductStatus;
use Modules\Product\Domain\ValueObjects\ProductType;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $slug,
        public readonly ProductType $type,
        public readonly ProductStatus $status,
        public readonly float $costPrice,
        public readonly float $sellingPrice,
        public readonly string $currency,
        public readonly bool $isPurchasable,
        public readonly bool $isSellable,
        public readonly bool $isStockable,
        public readonly bool $hasVariants,
        public readonly bool $hasSerialTracking,
        public readonly bool $hasBatchTracking,
        public readonly bool $hasExpiryTracking,
        public readonly ?int $categoryId = null,
        public readonly ?int $unitOfMeasureId = null,
        public readonly ?string $barcode = null,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?float $minSellingPrice = null,
        public readonly ?string $taxClass = null,
        public readonly ?float $weight = null,
        public readonly ?string $weightUnit = null,
        public readonly ?array $dimensions = null,
        public readonly ?array $images = null,
        public readonly ?array $tags = null,
        public readonly ?array $metadata = null,
    ) {}

    public function isAvailableForSale(): bool
    {
        return $this->status->isActive() && $this->isSellable;
    }

    public function isAvailableForPurchase(): bool
    {
        return $this->status->isActive() && $this->isPurchasable;
    }

    public function isTrackedInStock(): bool
    {
        return $this->type->isStockable() && $this->isStockable;
    }

    public function needsVariantSelection(): bool
    {
        return $this->type->requiresVariants() && $this->hasVariants;
    }
}
