<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $sku,
        public readonly ?int $categoryId,
        public readonly string $description,
        public readonly float $price,
        public readonly ?float $cost = null,
        public readonly ?float $weight = null,
        public readonly array $dimensions = [],
        public readonly array $images = [],
        public readonly array $tags = [],
        public readonly bool $isActive = true,
        public readonly ?int $createdBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            sku: $data['sku'],
            categoryId: $data['category_id'] ?? null,
            description: $data['description'] ?? '',
            price: (float) $data['price'],
            cost: isset($data['cost']) ? (float) $data['cost'] : null,
            weight: isset($data['weight']) ? (float) $data['weight'] : null,
            dimensions: $data['dimensions'] ?? [],
            images: $data['images'] ?? [],
            tags: $data['tags'] ?? [],
            isActive: $data['is_active'] ?? true,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'dimensions' => $this->dimensions,
            'images' => $this->images,
            'tags' => $this->tags,
            'is_active' => $this->isActive,
        ];

        if ($this->categoryId !== null) $data['category_id'] = $this->categoryId;
        if ($this->cost !== null) $data['cost'] = $this->cost;
        if ($this->weight !== null) $data['weight'] = $this->weight;
        if ($this->createdBy !== null) $data['created_by'] = $this->createdBy;

        return $data;
    }
}
