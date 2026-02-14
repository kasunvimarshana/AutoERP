<?php

namespace App\Modules\Product\DTOs;

class ProductDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $sku = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $unit_of_measure = 'pcs',
        public ?bool $is_variant_product = false,
        public ?string $status = 'active',
        public ?string $type = 'product',
        public ?float $tax_rate = 0,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            sku: $data['sku'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            unit_of_measure: $data['unit_of_measure'] ?? 'pcs',
            is_variant_product: $data['is_variant_product'] ?? false,
            status: $data['status'] ?? 'active',
            type: $data['type'] ?? 'product',
            tax_rate: isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0,
            settings: $data['settings'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'unit_of_measure' => $this->unit_of_measure,
            'is_variant_product' => $this->is_variant_product,
            'status' => $this->status,
            'type' => $this->type,
            'tax_rate' => $this->tax_rate,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
