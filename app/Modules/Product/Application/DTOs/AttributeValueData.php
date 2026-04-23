<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class AttributeValueData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $attribute_id,
        public readonly string $value,
        public readonly int $sort_order = 0,
        public readonly ?string $label = null,
        public readonly ?string $color_code = null,
        public readonly bool $is_active = true,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            attribute_id: (int) $data['attribute_id'],
            value: (string) $data['value'],
            sort_order: (int) ($data['sort_order'] ?? 0),
            label: isset($data['label']) ? (string) $data['label'] : null,
            color_code: isset($data['color_code']) ? (string) $data['color_code'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'attribute_id' => $this->attribute_id,
            'value' => $this->value,
            'sort_order' => $this->sort_order,
            'label' => $this->label,
            'color_code' => $this->color_code,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
        ];
    }
}
