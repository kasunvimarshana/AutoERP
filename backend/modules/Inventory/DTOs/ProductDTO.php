<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Core\DTOs\BaseDTO;

/**
 * Product DTO
 *
 * Data Transfer Object for product data.
 */
class ProductDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $sku = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $product_type = null,
        public ?string $category_id = null,
        public ?string $base_uom_id = null,
        public ?bool $track_inventory = true,
        public ?bool $track_batches = false,
        public ?bool $track_serials = false,
        public ?bool $has_expiry = false,
        public ?float $reorder_level = null,
        public ?float $reorder_quantity = null,
        public ?string $cost_method = 'fifo',
        public ?float $standard_cost = null,
        public ?float $selling_price = null,
        public ?string $status = 'active',
        public ?array $custom_attributes = null,
        public ?string $barcode = null,
        public ?array $variants = null,
        public ?array $attributes = null,
    ) {}

    /**
     * Convert to array for database operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'category_id' => $this->category_id,
            'base_uom_id' => $this->base_uom_id,
            'track_inventory' => $this->track_inventory,
            'track_batches' => $this->track_batches,
            'track_serials' => $this->track_serials,
            'has_expiry' => $this->has_expiry,
            'reorder_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'cost_method' => $this->cost_method,
            'standard_cost' => $this->standard_cost,
            'selling_price' => $this->selling_price,
            'status' => $this->status,
            'custom_attributes' => $this->custom_attributes,
            'barcode' => $this->barcode,
            'variants' => $this->variants,
            'attributes' => $this->attributes,
        ], fn ($value) => ! is_null($value));
    }
}
