<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockAdjusted;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class AdjustInventoryService implements AdjustInventoryServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $repository) {}

    public function execute(AdjustInventoryData $data): InventoryLevel
    {
        $level = $this->repository->findByProductWarehouseLocation(
            $data->productId,
            $data->warehouseId,
            $data->locationId,
            $data->batchId,
        );

        if (!$level) {
            $level = $this->repository->create([
                'tenant_id'          => $data->tenantId,
                'product_id'         => $data->productId,
                'warehouse_id'       => $data->warehouseId,
                'location_id'        => $data->locationId,
                'quantity_on_hand'   => $data->newQuantity,
                'quantity_reserved'  => 0,
                'quantity_available' => $data->newQuantity,
                'quantity_on_order'  => 0,
                'batch_id'           => $data->batchId,
            ]);
        } else {
            $level->adjust($data->newQuantity);
            $level = $this->repository->save($level);
        }

        Event::dispatch(new StockAdjusted($level->tenantId, $level->id));

        return $level;
    }
}
