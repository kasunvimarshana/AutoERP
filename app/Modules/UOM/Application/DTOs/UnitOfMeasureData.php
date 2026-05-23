<?php

declare(strict_types=1);

namespace Modules\UOM\Application\DTOs;

final readonly class UnitOfMeasureData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $name,
        public string $symbol,
        public string $type,
        public bool $isBase,
        public ?array $metadata,
        public ?int $rowVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(int|string $tenantId, array $attributes): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: isset($attributes['organization_unit_id']) ? (int) $attributes['organization_unit_id'] : null,
            name: (string) $attributes['name'],
            symbol: (string) $attributes['symbol'],
            type: (string) ($attributes['type'] ?? config('uom.types.0', 'UNIT')),
            isBase: (bool) ($attributes['is_base'] ?? false),
            metadata: $attributes['metadata'] ?? null,
            rowVersion: isset($attributes['row_version']) ? (int) $attributes['row_version'] : null,
        );
    }
}
