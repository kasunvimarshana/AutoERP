<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryLevel;

interface ReserveStockServiceInterface
{
    public function execute(int $levelId, float $qty): InventoryLevel;
}
