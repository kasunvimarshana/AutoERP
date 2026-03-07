<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly ?int $categoryId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly ?float $costPrice,
        public readonly string $currency,
        public readonly ?string $unit,
        public readonly ?float $weight,
        public readonly ?array $dimensions,
        public readonly string $status,
        public readonly bool $isActive,
        public readonly ?array $metadata,
        public readonly ?array $tags,
        public readonly ?array $images,
    ) {}

    public static function fromRequest(array $data, string $tenantId): self
    {
        return new self(
            tenantId:    $tenantId,
            categoryId:  $data['category_id'] ?? null,
            sku:         $data['sku'],
            name:        $data['name'],
            description: $data['description'] ?? null,
            price:       (float) $data['price'],
            costPrice:   isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            currency:    $data['currency'] ?? 'USD',
            unit:        $data['unit'] ?? null,
            weight:      isset($data['weight']) ? (float) $data['weight'] : null,
            dimensions:  $data['dimensions'] ?? null,
            status:      $data['status'] ?? 'active',
            isActive:    $data['is_active'] ?? true,
            metadata:    $data['metadata'] ?? null,
            tags:        $data['tags'] ?? null,
            images:      $data['images'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'   => $this->tenantId,
            'category_id' => $this->categoryId,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'cost_price'  => $this->costPrice,
            'currency'    => $this->currency,
            'unit'        => $this->unit,
            'weight'      => $this->weight,
            'dimensions'  => $this->dimensions,
            'status'      => $this->status,
            'is_active'   => $this->isActive,
            'metadata'    => $this->metadata,
            'tags'        => $this->tags,
            'images'      => $this->images,
        ];
    }
}
