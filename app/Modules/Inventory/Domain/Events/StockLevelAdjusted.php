<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Inventory\Domain\Entities\StockLevel;

class StockLevelAdjusted
{
    public function __construct(
        public readonly StockLevel $stockLevel,
        public readonly float $delta,
    ) {}
}
