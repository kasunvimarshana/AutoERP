<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class TransferStockCommand
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public ?int $variantId,
        public int $warehouseFromId,
        public int $warehouseToId,
        public string $quantity,
        public string $unitCost = '0',
        public ?string $notes = null,
        public ?int $createdBy = null,
    ) {}
}
