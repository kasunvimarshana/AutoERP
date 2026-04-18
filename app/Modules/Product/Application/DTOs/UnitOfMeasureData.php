<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class UnitOfMeasureData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $symbol,
        public readonly string $type = 'unit',
        public readonly bool $is_base = false,
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
            symbol: (string) $data['symbol'],
            type: (string) ($data['type'] ?? 'unit'),
            is_base: (bool) ($data['is_base'] ?? false),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
