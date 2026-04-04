<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryLevel;

interface ReleaseStockServiceInterface
{
    public function execute(int $levelId, float $qty): InventoryLevel;
}
