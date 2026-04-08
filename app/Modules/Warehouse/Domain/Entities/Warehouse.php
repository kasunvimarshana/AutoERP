<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseType;

final class Warehouse
{
    public function __construct(
        public readonly int           $id,
        public readonly string        $uuid,
        public readonly int           $tenantId,
        public readonly string        $name,
        public readonly string        $code,
        public readonly WarehouseType $type,
        public readonly bool          $isActive,
        public readonly ?array        $address  = null,
        public readonly ?array        $metadata = null,
    ) {}

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isType(string $type): bool
    {
        return $this->type->getValue() === $type;
    }
}
