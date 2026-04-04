<?php
namespace Modules\StockMovement\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\Events\StockMovementCreated;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class CreateStockMovementService implements CreateStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $repository) {}

    public function execute(StockMovementData $data): StockMovement
    {
        $movement = $this->repository->create([
            'tenant_id'        => $data->tenantId,
            'product_id'       => $data->productId,
            'warehouse_id'     => $data->warehouseId,
            'location_id'      => $data->locationId,
            'movement_type'    => $data->movementType,
            'quantity'         => $data->quantity,
            'reference_number' => $data->referenceNumber,
            'variant_id'       => $data->variantId,
            'batch_id'         => $data->batchId,
            'lot_number'       => $data->lotNumber,
            'serial_number'    => $data->serialNumber,
            'unit_cost'        => $data->unitCost,
            'notes'            => $data->notes,
            'moved_by'         => $data->movedBy,
            'moved_at'         => now(),
        ]);

        Event::dispatch(new StockMovementCreated($movement->tenantId, $movement->id));

        return $movement;
    }
}
