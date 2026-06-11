<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class AllocationPlanLineData
{
    public function __construct(
        public int $stockBalanceId,
        public string $quantity,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
    ) {}
}
