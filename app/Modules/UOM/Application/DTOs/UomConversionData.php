<?php

declare(strict_types=1);

namespace Modules\UOM\Application\DTOs;

final readonly class UomConversionData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $fromUomId,
        public int $toUomId,
        public string $factor,
        public ?int $itemId,
        public bool $isBidirectional,
        public bool $isActive,
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
            fromUomId: (int) $attributes['from_uom_id'],
            toUomId: (int) $attributes['to_uom_id'],
            factor: (string) $attributes['factor'],
            itemId: isset($attributes['item_id']) ? (int) $attributes['item_id'] : null,
            isBidirectional: (bool) ($attributes['is_bidirectional'] ?? true),
            isActive: (bool) ($attributes['is_active'] ?? true),
            metadata: $attributes['metadata'] ?? null,
            rowVersion: isset($attributes['row_version']) ? (int) $attributes['row_version'] : null,
        );
    }
}
