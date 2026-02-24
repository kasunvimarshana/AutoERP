<?php
namespace Modules\Inventory\Domain\Entities;
class Location
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $warehouseId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly ?string $parentId,
        public readonly bool $isActive,
    ) {}
}
