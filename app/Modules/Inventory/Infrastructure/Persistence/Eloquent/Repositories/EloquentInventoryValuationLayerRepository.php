<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;

class EloquentInventoryValuationLayerRepository extends EloquentRepository implements InventoryValuationLayerRepositoryInterface
{
    public function __construct(InventoryValuationLayerModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?InventoryValuationLayer
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByProduct(int $productId, int $warehouseId, string $valuationMethod): array
    {
        return $this->model->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('valuation_method', $valuationMethod)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findByProductOrdered(int $productId, int $warehouseId, string $direction = 'asc'): array
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('receipt_date', $direction)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }


    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(InventoryValuationLayer $layer, array $data): InventoryValuationLayer
    {
        $model = $this->model->findOrFail($layer->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(InventoryValuationLayer $layer): InventoryValuationLayer
    {
        $model = $this->model->findOrFail($layer->id);
        $updated = parent::update($model, [
            'quantity'            => $layer->quantity,
            'remaining_quantity'  => $layer->remainingQuantity,
            'unit_cost'           => $layer->unitCost,
            'total_cost'          => $layer->totalCost,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): InventoryValuationLayer
    {
        return new InventoryValuationLayer(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            warehouseId: $model->warehouse_id,
            valuationMethod: $model->valuation_method,
            quantity: (float) $model->quantity,
            remainingQuantity: (float) ($model->remaining_quantity ?? $model->quantity),
            unitCost: (float) $model->unit_cost,
            totalCost: (float) $model->total_cost,
            batchId: $model->batch_id,
            receiptDate: $model->receipt_date ? new \DateTimeImmutable($model->receipt_date) : null,
            referenceId: $model->reference_id,
            referenceType: $model->reference_type,
        );
    }
}
