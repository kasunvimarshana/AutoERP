<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\ReceiveStockData;

interface ReceiveStockServiceInterface
{
    /**
     * Inbound stock receipt orchestrator.
     *
     * Creates or updates the InventoryLevel for the given product/warehouse/location
     * and appends a new InventoryValuationLayer for cost tracking.
     *
     * @return array{level_id: int, layer_id: int}
     */
    public function execute(ReceiveStockData $data): array;
}
