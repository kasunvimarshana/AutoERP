<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;

class EloquentInventoryLevelRepository implements InventoryLevelRepositoryInterface
{
    public function __construct(private readonly InventoryLevelModel $model) {}

    private function toEntity(InventoryLevelModel $m): InventoryLevel
    {
        return new InventoryLevel($m->id, $m->tenant_id, $m->product_id, $m->warehouse_id, $m->location_id,
            (float)$m->quantity_on_hand, (float)$m->quantity_reserved, (float)$m->quantity_in_transit,
            $m->valuation_method, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?InventoryLevel
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByProduct(int $tenantId, int $productId, int $warehouseId): ?InventoryLevel
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->paginate(15);
    }

    public function upsert(int $tenantId, int $productId, int $warehouseId, ?int $locationId, string $valuationMethod): InventoryLevel
    {
        $m = $this->model->newQuery()->firstOrCreate(
            ['tenant_id' => $tenantId, 'product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['location_id' => $locationId, 'valuation_method' => $valuationMethod,
             'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'quantity_in_transit' => 0]
        );
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?InventoryLevel
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }
}
