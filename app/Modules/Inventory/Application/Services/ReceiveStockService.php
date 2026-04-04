<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\AddValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\DTOs\AddValuationLayerData;
use Modules\Inventory\Application\DTOs\ReceiveStockData;
use Modules\Inventory\Domain\Events\InventoryLevelUpdated;
use Modules\Inventory\Domain\Events\StockReceived;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

/**
 * Inbound stock receipt orchestrator.
 *
 * 1. Creates or updates the InventoryLevel (quantity_on_hand / quantity_available).
 * 2. Appends a new InventoryValuationLayer for FIFO/LIFO/Average cost tracking.
 * 3. Dispatches StockReceived + InventoryLevelUpdated events.
 */
class ReceiveStockService implements ReceiveStockServiceInterface
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $levelRepository,
        private readonly AddValuationLayerServiceInterface $addLayerService,
    ) {}

    public function execute(ReceiveStockData $data): array
    {
        // 1. Create or update inventory level
        $level = $this->levelRepository->findByProductWarehouseLocation(
            $data->productId,
            $data->warehouseId,
            $data->locationId,
            $data->batchId,
        );

        if ($level === null) {
            $level = $this->levelRepository->create([
                'tenant_id'          => $data->tenantId,
                'product_id'         => $data->productId,
                'warehouse_id'       => $data->warehouseId,
                'location_id'        => $data->locationId,
                'quantity_on_hand'   => $data->quantity,
                'quantity_reserved'  => 0.0,
                'quantity_available' => $data->quantity,
                'quantity_on_order'  => 0.0,
                'batch_id'           => $data->batchId,
                'stock_status'       => 'available',
            ]);
        } else {
            $level->receive($data->quantity);
            $level = $this->levelRepository->save($level);
        }

        // 2. Add valuation layer
        $layer = $this->addLayerService->execute(new AddValuationLayerData(
            tenantId:        $data->tenantId,
            productId:       $data->productId,
            warehouseId:     $data->warehouseId,
            valuationMethod: $data->valuationMethod,
            quantity:        $data->quantity,
            unitCost:        $data->unitCost,
            batchId:         $data->batchId,
            receiptDate:     $data->receiptDate ?? now()->toDateString(),
            referenceType:   $data->referenceType,
            referenceId:     $data->referenceId,
        ));

        // 3. Dispatch events
        Event::dispatch(new StockReceived($data->tenantId, $level->id));
        Event::dispatch(new InventoryLevelUpdated($data->tenantId, $level->id));

        return [
            'level_id' => $level->id,
            'layer_id' => $layer->id,
        ];
    }
}
