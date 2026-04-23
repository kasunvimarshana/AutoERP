<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ComboItemData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $combo_product_id,
        public readonly int $component_product_id,
        public readonly string $quantity,
        public readonly int $uom_id,
        public readonly ?int $component_variant_id = null,
        public readonly ?array $metadata = null,
        public readonly int $sort_order = 0,
        public readonly bool $is_optional = false,
        public readonly ?string $notes = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            combo_product_id: (int) $data['combo_product_id'],
            component_product_id: (int) $data['component_product_id'],
            quantity: (string) $data['quantity'],
            uom_id: (int) $data['uom_id'],
            component_variant_id: isset($data['component_variant_id']) ? (int) $data['component_variant_id'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_optional: (bool) ($data['is_optional'] ?? false),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'combo_product_id' => $this->combo_product_id,
            'component_product_id' => $this->component_product_id,
            'component_variant_id' => $this->component_variant_id,
            'quantity' => $this->quantity,
            'uom_id' => $this->uom_id,
            'metadata' => $this->metadata,
            'sort_order' => $this->sort_order,
            'is_optional' => $this->is_optional,
            'notes' => $this->notes,
        ];
    }
}
