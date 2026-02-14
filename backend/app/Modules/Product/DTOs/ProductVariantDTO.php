<?php

namespace App\Modules\Product\DTOs;

class ProductVariantDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $product_id = null,
        public ?string $tenant_id = null,
        public ?string $sku = null,
        public ?string $variant_name = null,
        public ?array $attributes = [],
        public ?float $cost_price = 0,
        public ?float $selling_price = 0,
        public ?string $barcode = null,
        public ?float $weight = 0,
        public ?array $dimensions = [],
        public ?string $status = 'active',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            product_id: $data['product_id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            sku: $data['sku'] ?? null,
            variant_name: $data['variant_name'] ?? null,
            attributes: $data['attributes'] ?? [],
            cost_price: isset($data['cost_price']) ? (float) $data['cost_price'] : 0,
            selling_price: isset($data['selling_price']) ? (float) $data['selling_price'] : 0,
            barcode: $data['barcode'] ?? null,
            weight: isset($data['weight']) ? (float) $data['weight'] : 0,
            dimensions: $data['dimensions'] ?? [],
            status: $data['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'tenant_id' => $this->tenant_id,
            'sku' => $this->sku,
            'variant_name' => $this->variant_name,
            'attributes' => $this->attributes,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'barcode' => $this->barcode,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'status' => $this->status,
        ], fn($value) => !is_null($value));
    }
}
