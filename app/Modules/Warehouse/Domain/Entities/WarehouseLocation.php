<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

class WarehouseLocation
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $warehouseId,
        public readonly ?string $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly bool $isPickable,
        public readonly bool $isReceivable,
    ) {}
}
