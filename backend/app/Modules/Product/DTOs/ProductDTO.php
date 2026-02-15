<?php

declare(strict_types=1);

namespace App\Modules\Product\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Product Data Transfer Object
 * 
 * Type-safe data container for product operations
 */
class ProductDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly int $tenant_id,
        public readonly ?int $organization_id = null,
        public readonly ?int $category_id = null,
        public readonly string $name,
        public readonly ?string $slug = null,
        public readonly ?string $sku = null,
        public readonly ?string $description = null,
        public readonly string $type = 'inventory', // inventory, service, combo, bundle, digital
        public readonly ?float $buying_price = null,
        public readonly ?float $selling_price = null,
        public readonly ?int $buying_unit_id = null,
        public readonly ?int $selling_unit_id = null,
        public readonly ?float $buying_discount_flat = null,
        public readonly ?float $buying_discount_percentage = null,
        public readonly ?float $selling_discount_flat = null,
        public readonly ?float $selling_discount_percentage = null,
        public readonly ?float $profit_margin_flat = null,
        public readonly ?float $profit_margin_percentage = null,
        public readonly ?float $minimum_stock = null,
        public readonly ?float $maximum_stock = null,
        public readonly ?float $reorder_level = null,
        public readonly ?int $reorder_quantity = null,
        public readonly bool $track_inventory = true,
        public readonly bool $track_serial = false,
        public readonly bool $track_batch = false,
        public readonly bool $track_expiry = false,
        public readonly ?int $shelf_life_days = null,
        public readonly bool $is_active = true,
        public readonly bool $is_featured = false,
        public readonly ?array $metadata = null,
    ) {
        $this->validate();
    }

    /**
     * Validate DTO data
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Product name is required');
        }

        if ($this->tenant_id <= 0) {
            throw new \InvalidArgumentException('Valid tenant_id is required');
        }

        if (!in_array($this->type, ['inventory', 'service', 'combo', 'bundle', 'digital'])) {
            throw new \InvalidArgumentException('Invalid product type');
        }

        if ($this->buying_price !== null && $this->buying_price < 0) {
            throw new \InvalidArgumentException('Buying price cannot be negative');
        }

        if ($this->selling_price !== null && $this->selling_price < 0) {
            throw new \InvalidArgumentException('Selling price cannot be negative');
        }

        if ($this->buying_discount_percentage !== null && ($this->buying_discount_percentage < 0 || $this->buying_discount_percentage > 100)) {
            throw new \InvalidArgumentException('Buying discount percentage must be between 0 and 100');
        }

        if ($this->selling_discount_percentage !== null && ($this->selling_discount_percentage < 0 || $this->selling_discount_percentage > 100)) {
            throw new \InvalidArgumentException('Selling discount percentage must be between 0 and 100');
        }

        if ($this->profit_margin_percentage !== null && $this->profit_margin_percentage < 0) {
            throw new \InvalidArgumentException('Profit margin percentage cannot be negative');
        }

        if ($this->track_expiry && $this->shelf_life_days !== null && $this->shelf_life_days <= 0) {
            throw new \InvalidArgumentException('Shelf life days must be positive when tracking expiry');
        }
    }

    /**
     * Calculate final buying price after discounts
     *
     * @return float|null
     */
    public function getFinalBuyingPrice(): ?float
    {
        if ($this->buying_price === null) {
            return null;
        }

        $price = $this->buying_price;

        if ($this->buying_discount_flat !== null) {
            $price -= $this->buying_discount_flat;
        }

        if ($this->buying_discount_percentage !== null) {
            $price -= ($price * $this->buying_discount_percentage / 100);
        }

        return max(0, $price);
    }

    /**
     * Calculate final selling price after discounts
     *
     * @return float|null
     */
    public function getFinalSellingPrice(): ?float
    {
        if ($this->selling_price === null) {
            return null;
        }

        $price = $this->selling_price;

        if ($this->selling_discount_flat !== null) {
            $price -= $this->selling_discount_flat;
        }

        if ($this->selling_discount_percentage !== null) {
            $price -= ($price * $this->selling_discount_percentage / 100);
        }

        return max(0, $price);
    }

    /**
     * Calculate profit margin
     *
     * @return float|null
     */
    public function calculateProfitMargin(): ?float
    {
        $buyingPrice = $this->getFinalBuyingPrice();
        $sellingPrice = $this->getFinalSellingPrice();

        if ($buyingPrice === null || $sellingPrice === null || $buyingPrice == 0) {
            return null;
        }

        return (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
    }
}
