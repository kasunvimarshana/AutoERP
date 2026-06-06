<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockTransferData
{
    /**
     * @param  list<StockTransferLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $transferDate,
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public ?int $organizationUnitId = null,
        public ?string $transferNumber = null,
        public ?int $fromWarehouseLocationId = null,
        public ?int $toWarehouseLocationId = null,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
