<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use DateTimeInterface;

class WarehouseLocation
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $warehouseId,
        public readonly ?string $parentId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $path,
        public readonly int $level,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
