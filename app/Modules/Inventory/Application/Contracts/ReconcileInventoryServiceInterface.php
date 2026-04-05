<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface ReconcileInventoryServiceInterface
{
    /**
     * Complete a cycle count:
     *  - Marks it as completed.
     *  - For each counted product, calculates the variance between counted and system quantity.
     *  - Adjusts inventory levels accordingly.
     *
     * @param array $countedItems [['product_id'=>int,'warehouse_id'=>int,'counted_qty'=>float], ...]
     */
    public function execute(int $cycleCountId, int $completedBy, array $countedItems): InventoryCycleCount;
}
