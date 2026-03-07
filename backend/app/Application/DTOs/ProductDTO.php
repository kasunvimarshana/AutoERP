<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for Product create / update operations.
 */
final class ProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly string $category,
        public readonly float $price,
        public readonly float $cost,
        public readonly int $stockQuantity,
        public readonly int $reorderPoint,
        public readonly int $reorderQuantity,
        public readonly ?string $description = null,
        public readonly ?string $unit = null,
        public readonly ?float $weight = null,
        public readonly ?array $dimensions = null,
        public readonly ?array $attributes = null,
        public readonly bool $isActive = true,
        public readonly bool $isTrackable = true,
        public readonly int|string|null $tenantId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:            $request->string('name')->toString(),
            sku:             $request->string('sku')->upper()->toString(),
            category:        $request->string('category')->toString(),
            price:           (float) $request->input('price'),
            cost:            (float) $request->input('cost', 0),
            stockQuantity:   (int) $request->input('stock_quantity', 0),
            reorderPoint:    (int) $request->input('reorder_point', 0),
            reorderQuantity: (int) $request->input('reorder_quantity', 0),
            description:     $request->input('description'),
            unit:            $request->input('unit'),
            weight:          $request->has('weight') ? (float) $request->input('weight') : null,
            dimensions:      $request->input('dimensions'),
            attributes:      $request->input('attributes'),
            isActive:        (bool) $request->input('is_active', true),
            isTrackable:     (bool) $request->input('is_trackable', true),
            tenantId:        $request->input('tenant_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'             => $this->name,
            'sku'              => $this->sku,
            'category'         => $this->category,
            'price'            => $this->price,
            'cost'             => $this->cost,
            'stock_quantity'   => $this->stockQuantity,
            'reorder_point'    => $this->reorderPoint,
            'reorder_quantity' => $this->reorderQuantity,
            'description'      => $this->description,
            'unit'             => $this->unit,
            'weight'           => $this->weight,
            'dimensions'       => $this->dimensions,
            'attributes'       => $this->attributes,
            'is_active'        => $this->isActive,
            'is_trackable'     => $this->isTrackable,
            'tenant_id'        => $this->tenantId,
        ], fn ($v) => $v !== null);
    }
}
