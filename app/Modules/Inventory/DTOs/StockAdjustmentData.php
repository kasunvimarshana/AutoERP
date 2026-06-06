<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Inventory\Enums\AdjustmentType;

final readonly class StockAdjustmentData
{
    /**
     * @param  list<StockAdjustmentLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $adjustmentDate,
        public AdjustmentType $adjustmentType,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $adjustmentNumber = null,
        public ?int $warehouseLocationId = null,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
