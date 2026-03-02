<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

final class ReorderRule
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly string $reorderPoint,
        public readonly string $reorderQuantity,
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
