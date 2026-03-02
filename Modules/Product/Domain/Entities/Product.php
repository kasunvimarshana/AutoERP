<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

final class Product
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $type,
        /** Inventory / stock-tracking UOM (base unit). */
        public readonly string $uom,
        /** Purchasing UOM. Defaults to $uom when null. */
        public readonly ?string $buyingUom,
        /** Sales UOM. Defaults to $uom when null. */
        public readonly ?string $sellingUom,
        public readonly string $costingMethod,
        public readonly string $costPrice,
        public readonly string $salePrice,
        public readonly ?string $barcode,
        public readonly string $status,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    /**
     * Returns the effective buying UOM (falls back to inventory UOM).
     */
    public function effectiveBuyingUom(): string
    {
        return $this->buyingUom ?? $this->uom;
    }

    /**
     * Returns the effective selling UOM (falls back to inventory UOM).
     */
    public function effectiveSellingUom(): string
    {
        return $this->sellingUom ?? $this->uom;
    }
}
