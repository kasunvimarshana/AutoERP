<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\LocationType;

final class Location
{
    public function __construct(
        public readonly int          $id,
        public readonly string       $uuid,
        public readonly int          $tenantId,
        public readonly int          $warehouseId,
        public readonly string       $name,
        public readonly string       $code,
        public readonly LocationType $type,
        public readonly int          $level,
        public readonly bool         $isActive,
        public readonly ?int         $parentId = null,
        public readonly ?string      $path     = null,
        public readonly ?float       $capacity = null,
        public readonly ?array       $metadata = null,
    ) {}

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isRootLocation(): bool
    {
        return $this->parentId === null;
    }

    public function hasCapacity(): bool
    {
        return $this->capacity !== null;
    }
}
