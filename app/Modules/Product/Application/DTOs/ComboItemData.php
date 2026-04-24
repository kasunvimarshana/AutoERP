<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ComboItemData
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly int $combo_product_id,
        public readonly int $component_product_id,
        public readonly string $quantity,
        public readonly int $uom_id,
        public readonly ?int $tenant_id = null,
        public readonly ?int $component_variant_id = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            combo_product_id: (int) $data['combo_product_id'],
            component_product_id: (int) $data['component_product_id'],
            quantity: (string) $data['quantity'],
            uom_id: (int) $data['uom_id'],
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            component_variant_id: isset($data['component_variant_id']) ? (int) $data['component_variant_id'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
