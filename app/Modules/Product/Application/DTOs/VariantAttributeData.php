<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class VariantAttributeData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly int $attribute_id,
        public readonly bool $is_required = false,
        public readonly bool $is_variation_axis = true,
        public readonly int $display_order = 0,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            attribute_id: (int) $data['attribute_id'],
            is_required: (bool) ($data['is_required'] ?? false),
            is_variation_axis: (bool) ($data['is_variation_axis'] ?? true),
            display_order: (int) ($data['display_order'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
