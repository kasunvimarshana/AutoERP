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
        public readonly string $name,
        public readonly ?string $sku = null,
        public readonly bool $is_default = false,
        public readonly bool $is_active = true,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            name: (string) $data['name'],
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
