<?php

namespace App\Core\DTOs\Inventory;

use App\Core\DTOs\BaseDTO;

/**
 * ProductDTO
 * 
 * Data Transfer Object for Product operations
 */
class ProductDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $unit,
        public readonly ?string $slug = null,
        public readonly ?string $sku = null,
        public readonly ?string $barcode = null,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
        public readonly ?string $brand = null,
        public readonly ?bool $is_variant_product = false,
        public readonly ?bool $track_stock = true,
        public readonly ?bool $track_batch = false,
        public readonly ?bool $track_serial = false,
        public readonly ?float $min_stock_level = null,
        public readonly ?float $max_stock_level = null,
        public readonly ?float $reorder_level = null,
        public readonly ?float $reorder_quantity = null,
        public readonly ?float $cost_price = null,
        public readonly ?float $selling_price = null,
        public readonly ?float $tax_rate = null,
        public readonly ?bool $tax_inclusive = false,
        public readonly ?float $weight = null,
        public readonly ?array $dimensions = null,
        public readonly ?array $images = null,
        public readonly ?array $attributes = null,
        public readonly ?bool $is_active = true,
        public readonly ?bool $is_purchasable = true,
        public readonly ?bool $is_sellable = true,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Convert to array for database insertion/update
     * Filters out null values
     */
    public function toDatabase(): array
    {
        return self::filterNulls($this->toArray());
    }
}
