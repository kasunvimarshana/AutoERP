<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class AdjustStockCommand
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public ?int $variantId,
        public int $warehouseId,
        public string $quantity,
        public string $type,
        public string $reason,
        public ?int $referenceId = null,
        public string $unitCost = '0',
        public ?int $createdBy = null,
    ) {}
}
