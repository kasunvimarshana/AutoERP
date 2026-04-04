<?php
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseZoneModel;

class EloquentWarehouseZoneRepository extends EloquentRepository implements WarehouseZoneRepositoryInterface
{
    public function __construct(WarehouseZoneModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?WarehouseZone
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByWarehouse(int $warehouseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('warehouse_id', $warehouseId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): WarehouseZone
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(WarehouseZone $zone, array $data): WarehouseZone
    {
        $model = $this->model->findOrFail($zone->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(WarehouseZone $zone): bool
    {
        $model = $this->model->findOrFail($zone->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): WarehouseZone
    {
        return new WarehouseZone(
            id: $model->id,
            warehouseId: $model->warehouse_id,
            code: $model->code,
            name: $model->name,
            type: $model->type,
            status: $model->status,
            description: $model->description,
        );
    }
}
