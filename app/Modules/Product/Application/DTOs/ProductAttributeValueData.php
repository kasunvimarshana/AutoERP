<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductAttributeValueData
{
    public function __construct(
        public readonly int $attribute_id,
        public readonly string $value,
        public readonly int $sort_order = 0,
        public readonly ?int $tenant_id = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            attribute_id: (int) $data['attribute_id'],
            value: (string) $data['value'],
            sort_order: (int) ($data['sort_order'] ?? 0),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
