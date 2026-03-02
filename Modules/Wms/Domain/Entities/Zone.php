<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Entities;

final class Zone
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
