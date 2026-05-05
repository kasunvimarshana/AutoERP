<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductVariantData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly ?int $org_unit_id,
        public readonly string $name,
        public readonly ?string $sku = null,
        public readonly bool $is_default = false,
        public readonly bool $is_active = true,
        public readonly ?string $purchase_price = null,
        public readonly ?string $sales_price = null,
        public readonly ?array $metadata = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            org_unit_id: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            name: (string) $data['name'],
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            purchase_price: isset($data['purchase_price']) ? (string) $data['purchase_price'] : null,
            sales_price: isset($data['sales_price']) ? (string) $data['sales_price'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            rowVersion: (int) ($data['row_version'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
