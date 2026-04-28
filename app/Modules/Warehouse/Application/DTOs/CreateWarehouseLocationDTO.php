<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

class CreateWarehouseLocationDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly ?int $orgUnitId,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly ?string $code,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly bool $isPickable,
        public readonly bool $isReceivable,
        public readonly ?string $capacity,
        public readonly ?array $metadata,
    ) {}
}
