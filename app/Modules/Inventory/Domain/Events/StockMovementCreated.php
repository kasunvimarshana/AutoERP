<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Inventory\Domain\Entities\StockMovement;

class StockMovementCreated
{
    public function __construct(
        public readonly StockMovement $movement,
    ) {}
}
