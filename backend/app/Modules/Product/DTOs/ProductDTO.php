<?php

namespace App\Modules\Product\DTOs;

use App\Modules\Product\Models\Product;

readonly class ProductDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $sku,
        public ?string $description,
        public float $price,
        public float $costPrice,
        public ?string $category,
        public ?string $unit,
        public bool $isActive,
        public ?int $tenantId,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            sku: $data['sku'],
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            costPrice: (float) ($data['cost_price'] ?? 0),
            category: $data['category'] ?? null,
            unit: $data['unit'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
            tenantId: $data['tenant_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            sku: $product->sku,
            description: $product->description,
            price: (float) $product->price,
            costPrice: (float) $product->cost_price,
            category: $product->category,
            unit: $product->unit,
            isActive: $product->is_active,
            tenantId: $product->tenant_id,
            metadata: $product->metadata ?? [],
        );
    }
}
