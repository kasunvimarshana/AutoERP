<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

/**
 * Represents a Unit of Measure conversion factor for a specific product.
 *
 * The conversion factor expresses how many inventory-UOM units correspond
 * to one unit of the from_uom. For example, if a product is tracked in
 * 'pcs' (inventory UOM) and purchased in 'box', a factor of 12 means
 * 1 box = 12 pcs.
 */
final class UomConversion
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly int $tenantId,
        public readonly string $fromUom,
        public readonly string $toUom,
        public readonly string $factor,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
