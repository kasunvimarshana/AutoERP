<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class TransferStockCommand
{
    public function __construct(
        public int $tenantId,
        public int $sourceWarehouseId,
        public int $destinationWarehouseId,
        public int $productId,
        public string $quantity,
        public string $unitCost,
        public ?string $notes,
    ) {}
}
