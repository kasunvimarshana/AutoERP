<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface ReconcileInventoryServiceInterface
{
    public function execute(int $cycleCountId, int $reconciledBy): InventoryCycleCount;
}
