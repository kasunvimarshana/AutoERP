<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductAttributeData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $type = 'select',
        public readonly bool $is_required = false,
        public readonly ?int $group_id = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            type: isset($data['type']) ? (string) $data['type'] : 'select',
            is_required: (bool) ($data['is_required'] ?? false),
            group_id: isset($data['group_id']) ? (int) $data['group_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
