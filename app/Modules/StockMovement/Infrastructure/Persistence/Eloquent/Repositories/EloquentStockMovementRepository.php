<?php
namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

class EloquentStockMovementRepository extends EloquentRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovementModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?StockMovement
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function findByReference(string $referenceNumber): ?StockMovement
    {
        $model = $this->model->where('reference_number', $referenceNumber)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): StockMovement
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(StockMovement $movement, array $data): StockMovement
    {
        $model = $this->model->findOrFail($movement->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): StockMovement
    {
        return new StockMovement(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            warehouseId: $model->warehouse_id,
            locationId: $model->location_id,
            movementType: $model->movement_type,
            quantity: (float) $model->quantity,
            referenceNumber: $model->reference_number,
            variantId: $model->variant_id,
            batchId: $model->batch_id,
            lotNumber: $model->lot_number,
            serialNumber: $model->serial_number,
            unitCost: $model->unit_cost !== null ? (float) $model->unit_cost : null,
            relatedMovementId: $model->related_movement_id,
            notes: $model->notes,
            movedAt: $model->moved_at ? new \DateTimeImmutable((string) $model->moved_at) : null,
            movedBy: $model->moved_by,
        );
    }
}
