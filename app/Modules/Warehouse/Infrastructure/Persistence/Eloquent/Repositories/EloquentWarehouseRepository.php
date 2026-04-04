<?php
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Warehouse
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function findByCode(int $tenantId, string $code): ?Warehouse
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): Warehouse
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $model = $this->model->findOrFail($warehouse->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(Warehouse $warehouse): bool
    {
        $model = $this->model->findOrFail($warehouse->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): Warehouse
    {
        return new Warehouse(
            id: $model->id,
            tenantId: $model->tenant_id,
            code: $model->code,
            name: $model->name,
            type: $model->type,
            status: $model->status,
            address: $model->address,
            city: $model->city,
            country: $model->country,
            isDefault: (bool) $model->is_default,
        );
    }
}
