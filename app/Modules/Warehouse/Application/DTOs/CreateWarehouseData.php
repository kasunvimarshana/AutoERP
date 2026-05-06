<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

class CreateWarehouseData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $orgUnitId,
        public readonly string $name,
        public readonly ?string $code,
        public readonly ?string $imagePath,
        public readonly string $type,
        public readonly ?int $addressId,
        public readonly bool $isActive,
        public readonly bool $isDefault,
        public readonly ?array $metadata,
    ) {}
}
