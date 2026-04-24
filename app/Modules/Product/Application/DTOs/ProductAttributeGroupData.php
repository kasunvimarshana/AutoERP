<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductAttributeGroupData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
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
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
