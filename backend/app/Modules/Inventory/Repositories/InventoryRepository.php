<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $query = Inventory::with('product');

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get();
    }

    public function find(int $id): ?Inventory
    {
        return Inventory::with('product')->find($id);
    }

    public function findByProduct(int $productId): ?Inventory
    {
        return Inventory::where('product_id', $productId)->first();
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(int $id, array $data): Inventory
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->update($data);
        return $inventory->fresh('product');
    }

    public function delete(int $id): bool
    {
        $inventory = Inventory::findOrFail($id);
        return $inventory->delete();
    }

    public function paginate(
        int $perPage,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDir
    ): LengthAwarePaginator {
        $query = Inventory::with('product');

        foreach ($filters as $column => $value) {
            if (!empty($value)) {
                $query->where($column, $value);
            }
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })->orWhere('warehouse_location', 'like', "%{$search}%");
        }

        $allowedSorts = ['quantity', 'warehouse_location', 'created_at', 'last_restocked_at'];
        $sortBy  = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function adjustQuantity(int $id, int $delta, string $reason): Inventory
    {
        return DB::transaction(function () use ($id, $delta, $reason) {
            $inventory = Inventory::lockForUpdate()->findOrFail($id);
            $newQty    = $inventory->quantity + $delta;

            if ($newQty < 0) {
                throw new \RuntimeException("Insufficient inventory. Current: {$inventory->quantity}, Delta: {$delta}");
            }

            $updateData = ['quantity' => $newQty];

            if ($delta > 0) {
                $updateData['last_restocked_at'] = now();
            }

            $inventory->update($updateData);

            return $inventory->fresh('product');
        });
    }
}
