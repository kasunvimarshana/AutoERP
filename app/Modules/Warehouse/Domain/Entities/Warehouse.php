<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

class Warehouse
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly bool $isDefault,
    ) {}
}
