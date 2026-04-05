<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;

class EloquentInventoryBatchRepository implements InventoryBatchRepositoryInterface
{
    public function __construct(private readonly InventoryBatchModel $model) {}

    private function toEntity(InventoryBatchModel $m): InventoryBatch
    {
        return new InventoryBatch($m->id, $m->tenant_id, $m->product_id, $m->warehouse_id,
            $m->batch_number, $m->lot_number, $m->serial_number,
            (float)$m->quantity, (float)$m->quantity_remaining, (float)$m->cost_price,
            $m->manufactured_at, $m->expires_at, $m->received_at, $m->status, $m->reference,
            $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?InventoryBatch
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByProduct(int $tenantId, int $productId, int $warehouseId): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->paginate(15);
    }

    public function findActiveBatches(int $tenantId, int $productId, int $warehouseId, string $strategy = 'fifo'): array
    {
        $q = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0);

        if ($strategy === 'lifo') {
            $q->orderByDesc('received_at');
        } elseif ($strategy === 'fefo') {
            $q->orderBy('expires_at')->orderBy('received_at');
        } else {
            $q->orderBy('received_at');
        }

        return $q->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findByBatchNumber(int $tenantId, int $productId, string $batchNumber): ?InventoryBatch
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function create(array $data): InventoryBatch
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?InventoryBatch
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }
}
