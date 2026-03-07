<?php

namespace App\Repositories;

use App\Models\Inventory;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function findById(int $id): ?Inventory
    {
        return Inventory::with('warehouse')->find($id);
    }

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?Inventory
    {
        return Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    public function findByProductId(int $productId): Collection
    {
        return Inventory::with('warehouse')
            ->where('product_id', $productId)
            ->get();
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update($data);

        return $inventory->fresh(['warehouse']);
    }

    public function delete(Inventory $inventory): bool
    {
        return $inventory->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Inventory::with('warehouse')
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('product_id', $filters['product_id'])
            )
            ->when(isset($filters['product_ids']) && is_array($filters['product_ids']), fn (Builder $q) => $q->whereIn('product_id', $filters['product_ids'])
            )
            ->when(isset($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id'])
            )
            ->when(isset($filters['low_stock']), fn (Builder $q) => $q->whereRaw('(quantity - reserved_quantity) <= reorder_level')
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
