<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\ReceiveStockData;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface ReceiveStockServiceInterface
{
    public function execute(ReceiveStockData $data): InventoryLevel;
}
