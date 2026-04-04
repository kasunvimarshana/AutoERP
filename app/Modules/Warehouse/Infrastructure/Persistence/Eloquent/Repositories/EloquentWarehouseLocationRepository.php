<?php
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    public function __construct(WarehouseLocationModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?WarehouseLocation
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByBarcode(string $barcode): ?WarehouseLocation
    {
        $model = $this->model->where('barcode', $barcode)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findByZone(int $zoneId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('zone_id', $zoneId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): WarehouseLocation
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(WarehouseLocation $location, array $data): WarehouseLocation
    {
        $model = $this->model->findOrFail($location->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(WarehouseLocation $location): bool
    {
        $model = $this->model->findOrFail($location->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): WarehouseLocation
    {
        return new WarehouseLocation(
            id: $model->id,
            warehouseId: $model->warehouse_id,
            zoneId: $model->zone_id,
            code: $model->code,
            barcode: $model->barcode,
            locationType: $model->location_type,
            isActive: (bool) $model->is_active,
            aisle: $model->aisle,
            bay: $model->bay,
            level: $model->level,
            bin: $model->bin,
            maxWeight: $model->max_weight !== null ? (float) $model->max_weight : null,
            maxVolume: $model->max_volume !== null ? (float) $model->max_volume : null,
        );
    }
}
