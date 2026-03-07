<?php

namespace App\Modules\Product\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly float $price,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
        public readonly string $status = 'active',
        public readonly ?array $metadata = null,
        public readonly ?string $createdBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            sku: $data['sku'],
            price: (float) $data['price'],
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            status: $data['status'] ?? 'active',
            metadata: $data['metadata'] ?? null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_by' => $this->createdBy,
        ];
    }
}
