<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function allForTenant(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(InventoryItem::class)
            ->where('tenant_id', $tenantId)
            ->allowedFilters([
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::scope('low_stock'),
                'sku',
            ])
            ->allowedSorts(['quantity', 'reserved_quantity', 'created_at', 'sku'])
            ->with(['warehouse'])
            ->paginate($perPage);
    }

    public function findById(int $id, int $tenantId): ?InventoryItem
    {
        return InventoryItem::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['warehouse'])
            ->first();
    }

    public function findByProductAndWarehouse(int $productId, int $warehouseId, int $tenantId): ?InventoryItem
    {
        return InventoryItem::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        return $item->fresh(['warehouse']);
    }

    public function delete(InventoryItem $item): bool
    {
        return (bool) $item->delete();
    }

    public function incrementQuantity(InventoryItem $item, int $amount): InventoryItem
    {
        $item->increment('quantity', $amount);

        return $item->fresh();
    }

    public function decrementQuantity(InventoryItem $item, int $amount): InventoryItem
    {
        $item->decrement('quantity', $amount);

        return $item->fresh();
    }

    public function setQuantity(InventoryItem $item, int $quantity): InventoryItem
    {
        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    public function incrementReserved(InventoryItem $item, int $amount): InventoryItem
    {
        $item->increment('reserved_quantity', $amount);

        return $item->fresh();
    }

    public function decrementReserved(InventoryItem $item, int $amount): InventoryItem
    {
        $item->decrement('reserved_quantity', $amount);

        return $item->fresh();
    }

    public function getLowStockItems(int $tenantId): Collection
    {
        return InventoryItem::forTenant($tenantId)
            ->lowStock()
            ->with(['warehouse'])
            ->get();
    }

    public function deleteByProduct(int $productId, int $tenantId): int
    {
        return InventoryItem::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
