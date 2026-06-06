<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockPostingResult
{
    public function __construct(
        public int $movementId,
        public string $movementNumber,
        public string $status,
        public string $quantity,
        public string $unitCost,
        public string $totalCost,
        public string $balanceQuantityAfter,
        public string $balanceValueAfter,
    ) {}
}
