<?php
namespace Modules\Inventory\Infrastructure\Repositories;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\WarehouseModel;
class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return WarehouseModel::with('locations')->find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = WarehouseModel::query();
        if (isset($filters['is_active'])) $query->where('is_active', (bool)$filters['is_active']);
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('code', 'like', '%'.$filters['search'].'%');
            });
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return WarehouseModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $warehouse = WarehouseModel::findOrFail($id);
        $warehouse->update($data);
        return $warehouse->fresh();
    }
    public function delete(string $id): bool
    {
        return WarehouseModel::findOrFail($id)->delete();
    }
}
