<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;

class EloquentInventoryValuationLayerRepository implements InventoryValuationLayerRepositoryInterface
{
    public function __construct(private readonly InventoryValuationLayerModel $model) {}

    private function toEntity(InventoryValuationLayerModel $m): InventoryValuationLayer
    {
        return new InventoryValuationLayer($m->id, $m->tenant_id, $m->product_id, $m->warehouse_id,
            (float)$m->quantity, (float)$m->quantity_remaining, (float)$m->unit_cost,
            $m->received_at, $m->reference, $m->batch_id, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?InventoryValuationLayer
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByProduct(int $tenantId, int $productId, int $warehouseId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('received_at')
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findLayersForConsumption(int $tenantId, int $productId, int $warehouseId, string $method): array
    {
        $q = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_remaining', '>', 0);

        if ($method === 'lifo') {
            $q->orderByDesc('received_at');
        } else {
            $q->orderBy('received_at'); // fifo and average
        }

        return $q->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): InventoryValuationLayer
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?InventoryValuationLayer
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function getAverageCost(int $tenantId, int $productId, int $warehouseId): float
    {
        $result = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_remaining', '>', 0)
            ->selectRaw('SUM(unit_cost * quantity_remaining) / NULLIF(SUM(quantity_remaining), 0) as avg_cost')
            ->value('avg_cost');
        return $result ? (float)$result : 0.0;
    }
}
