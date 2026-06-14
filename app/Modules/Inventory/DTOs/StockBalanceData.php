<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockBalanceData
{
    /**
     * Identifies one aggregate balance in the item's current base UOM.
     *
     * Serial identity is tracked separately by inventory serial records and allocation lines.
     */
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
