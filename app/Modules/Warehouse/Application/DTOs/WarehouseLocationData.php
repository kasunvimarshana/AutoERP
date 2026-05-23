<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

final readonly class WarehouseLocationData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $warehouseId,
        public ?int $organizationUnitId,
        public ?int $parentId,
        public string $name,
        public ?string $code = null,
        public ?string $path = null,
        public int $depth = 0,
        public string $type = 'bin',
        public bool $isActive = true,
        public bool $isPickable = true,
        public bool $isReceivable = true,
        public null|int|float|string $capacity = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, int|string $warehouseId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            warehouseId: (int) $warehouseId,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            name: (string) $data['name'],
            code: $data['code'] ?? null,
            path: $data['path'] ?? null,
            depth: isset($data['depth']) ? (int) $data['depth'] : 0,
            type: (string) ($data['type'] ?? 'bin'),
            isActive: (bool) ($data['is_active'] ?? true),
            isPickable: (bool) ($data['is_pickable'] ?? true),
            isReceivable: (bool) ($data['is_receivable'] ?? true),
            capacity: $data['capacity'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
