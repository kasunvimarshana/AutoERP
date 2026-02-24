<?php

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\InventoryLotModel;

class InventoryLotRepository implements InventoryLotRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return InventoryLotModel::find($id);
    }

    public function findByLotNumber(string $tenantId, string $productId, string $lotNumber): ?object
    {
        return InventoryLotModel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('lot_number', $lotNumber)
            ->first();
    }

    public function create(array $data): object
    {
        return InventoryLotModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $lot = InventoryLotModel::findOrFail($id);
        $lot->update($data);
        return $lot->fresh();
    }

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = InventoryLotModel::where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tracking_type'])) {
            $query->where('tracking_type', $filters['tracking_type']);
        }

        if (! empty($filters['expiry_before'])) {
            $query->where('expiry_date', '<=', $filters['expiry_before']);
        }

        return $query->paginate($perPage);
    }
}
