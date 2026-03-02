<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class ReturnStockCommand
{
    public function __construct(
        public int $tenantId,
        public int $warehouseId,
        public int $productId,
        public string $quantity,
        public string $unitCost,
        public ?string $referenceType,
        public ?string $referenceId,
        public ?string $notes,
    ) {}
}
