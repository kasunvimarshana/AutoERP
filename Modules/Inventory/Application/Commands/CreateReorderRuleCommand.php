<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class CreateReorderRuleCommand
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public int $warehouseId,
        public string $reorderPoint,
        public string $reorderQuantity,
        public bool $isActive = true,
    ) {}
}
