<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class UnitOfMeasureData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $symbol,
        public readonly string $type = 'unit',
        public readonly bool $isBase = false,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            symbol: (string) $data['symbol'],
            type: (string) ($data['type'] ?? 'unit'),
            isBase: (bool) ($data['is_base'] ?? false),
            rowVersion: (int) ($data['row_version'] ?? 1),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
