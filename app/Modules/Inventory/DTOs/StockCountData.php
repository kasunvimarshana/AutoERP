<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockCountData
{
    /**
     * @param  list<StockCountLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $countDate,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $countNumber = null,
        public string $countType = 'stock_count',
        public ?int $warehouseLocationId = null,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
