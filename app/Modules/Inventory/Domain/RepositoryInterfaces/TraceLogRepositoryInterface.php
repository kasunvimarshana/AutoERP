<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockMovement;

interface TraceLogRepositoryInterface
{
    public function recordForMovement(StockMovement $movement): void;
}
