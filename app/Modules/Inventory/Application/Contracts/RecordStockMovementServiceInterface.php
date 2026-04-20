<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\StockMovement;

interface RecordStockMovementServiceInterface
{
    public function execute(array $data): StockMovement;
}
