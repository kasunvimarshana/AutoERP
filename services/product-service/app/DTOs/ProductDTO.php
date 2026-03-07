<?php

namespace App\DTOs;

/**
 * Data Transfer Object for creating / updating a product.
 */
final class ProductDTO
{
    public function __construct(
        public readonly string      $name,
        public readonly string      $sku,
        public readonly float       $price,
        public readonly ?int        $tenantId       = null,
        public readonly ?int        $categoryId     = null,
        public readonly ?string     $description    = null,
        public readonly float       $costPrice      = 0.0,
        public readonly ?string     $unit           = null,
        public readonly ?float      $weight         = null,
        public readonly array       $dimensions     = [],
        public readonly array       $images         = [],
        public readonly array       $attributes     = [],
        public readonly bool        $isActive       = true,
        public readonly ?int        $minStockLevel  = null,
        public readonly ?int        $maxStockLevel  = null,
        public readonly ?int        $reorderPoint   = null,
    ) {}

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    public static function fromArray(array $data): self
    {
        return new self(
            name:           $data['name'],
            sku:            $data['sku'],
            price:          (float) $data['price'],
            tenantId:       isset($data['tenant_id'])       ? (int) $data['tenant_id']       : null,
            categoryId:     isset($data['category_id'])     ? (int) $data['category_id']     : null,
            description:    $data['description']   ?? null,
            costPrice:      isset($data['cost_price'])      ? (float) $data['cost_price']    : 0.0,
            unit:           $data['unit']          ?? null,
            weight:         isset($data['weight'])          ? (float) $data['weight']         : null,
            dimensions:     $data['dimensions']    ?? [],
            images:         $data['images']        ?? [],
            attributes:     $data['attributes']    ?? [],
            isActive:       isset($data['is_active'])       ? (bool) $data['is_active']       : true,
            minStockLevel:  isset($data['min_stock_level']) ? (int) $data['min_stock_level']  : null,
            maxStockLevel:  isset($data['max_stock_level']) ? (int) $data['max_stock_level']  : null,
            reorderPoint:   isset($data['reorder_point'])   ? (int) $data['reorder_point']    : null,
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    /**
     * Convert to array suitable for Eloquent create/fill.
     */
    public function toArray(): array
    {
        $data = [
            'name'        => $this->name,
            'sku'         => $this->sku,
            'price'       => $this->price,
            'cost_price'  => $this->costPrice,
            'is_active'   => $this->isActive,
            'dimensions'  => $this->dimensions,
            'images'      => $this->images,
            'attributes'  => $this->attributes,
        ];

        if ($this->tenantId !== null) {
            $data['tenant_id'] = $this->tenantId;
        }

        if ($this->categoryId !== null) {
            $data['category_id'] = $this->categoryId;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->unit !== null) {
            $data['unit'] = $this->unit;
        }

        if ($this->weight !== null) {
            $data['weight'] = $this->weight;
        }

        if ($this->minStockLevel !== null) {
            $data['min_stock_level'] = $this->minStockLevel;
        }

        if ($this->maxStockLevel !== null) {
            $data['max_stock_level'] = $this->maxStockLevel;
        }

        if ($this->reorderPoint !== null) {
            $data['reorder_point'] = $this->reorderPoint;
        }

        return $data;
    }
}
