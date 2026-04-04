<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\AddValuationLayerServiceInterface;
use Modules\Inventory\Application\DTOs\AddValuationLayerData;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\Events\ValuationLayerCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class AddValuationLayerService implements AddValuationLayerServiceInterface
{
    public function __construct(
        private readonly InventoryValuationLayerRepositoryInterface $repository
    ) {}

    public function execute(AddValuationLayerData $data): InventoryValuationLayer
    {
        $totalCost = $data->quantity * $data->unitCost;

        $layer = $this->repository->create([
            'tenant_id'          => $data->tenantId,
            'product_id'         => $data->productId,
            'warehouse_id'       => $data->warehouseId,
            'valuation_method'   => $data->valuationMethod,
            'quantity'           => $data->quantity,
            'remaining_quantity' => $data->quantity,
            'unit_cost'          => $data->unitCost,
            'total_cost'         => $totalCost,
            'batch_id'           => $data->batchId,
            'receipt_date'       => $data->receiptDate,
            'reference_type'     => $data->referenceType,
            'reference_id'       => $data->referenceId,
        ]);

        Event::dispatch(new ValuationLayerCreated($data->tenantId, $layer->id));

        return $layer;
    }
}
