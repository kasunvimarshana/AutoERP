<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

interface AddValuationLayerServiceInterface
{
    /**
     * Create a new inbound valuation layer for a product in a warehouse.
     * This is the standalone service used by inbound flows (GR, returns, adjustments).
     */
    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        string $reference,
        ?int $batchId = null,
    ): InventoryValuationLayer;
}
