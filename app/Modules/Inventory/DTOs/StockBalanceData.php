<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockBalanceData
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
    ) {}
}
