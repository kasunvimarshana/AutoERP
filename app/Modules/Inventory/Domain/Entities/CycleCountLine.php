<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class CycleCountLine
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $cycleCountId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly ?string $batchLotId,
        public readonly float $systemQuantity,
        public readonly float $countedQuantity,
        public readonly float $variance,
        public readonly string $status,
        public readonly ?string $notes,
    ) {}
}
