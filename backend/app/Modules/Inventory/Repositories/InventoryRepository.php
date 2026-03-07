<?php
namespace App\Modules\Inventory\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Inventory;

class InventoryRepository implements RepositoryInterface
{
    public function __construct(private Inventory $model) {}

    public function all(array $filters = [], array $relations = [])
    {
        $query = $this->model->newQuery()->with($relations);

        if (isset($filters['tenant_id'])) {
            $query->where('inventories.tenant_id', $filters['tenant_id']);
        }
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (isset($filters['warehouse_location'])) {
            $query->where('warehouse_location', 'like', "%{$filters['warehouse_location']}%");
        }
        if (isset($filters['low_stock'])) {
            $query->whereRaw('quantity <= reorder_level');
        }
        if (isset($filters['product_name'])) {
            $query->whereHas('product', fn($q) =>
                $q->where('name', 'like', "%{$filters['product_name']}%")
            );
        }

        return $query;
    }

    public function find(int $id, array $relations = []): ?Inventory
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data): Inventory
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Inventory
    {
        $inventory = $this->find($id);
        $inventory->update($data);
        return $inventory->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $relations = [])
    {
        return $this->all($filters, $relations)->paginate($perPage);
    }

    public function adjustQuantity(int $id, int $delta): Inventory
    {
        $inventory = $this->find($id);
        $inventory->increment('quantity', $delta);
        return $inventory->fresh();
    }

    public function reserveQuantity(int $id, int $quantity): bool
    {
        $inventory = $this->find($id);
        if ($inventory->available_quantity < $quantity) {
            return false;
        }
        $inventory->increment('reserved_quantity', $quantity);
        return true;
    }

    public function releaseReservation(int $id, int $quantity): void
    {
        $inventory = $this->find($id);
        $inventory->decrement('reserved_quantity', min($quantity, $inventory->reserved_quantity));
    }
}
