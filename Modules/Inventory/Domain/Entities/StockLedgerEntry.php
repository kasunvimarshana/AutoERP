<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

final class StockLedgerEntry
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $productId,
        public readonly string $transactionType,
        public readonly string $quantity,
        public readonly string $unitCost,
        public readonly string $totalCost,
        public readonly ?string $referenceType,
        public readonly ?string $referenceId,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
    ) {}
}
