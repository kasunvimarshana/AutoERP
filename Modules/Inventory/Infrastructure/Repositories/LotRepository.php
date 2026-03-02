<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLot;
use Modules\Inventory\Infrastructure\Models\InventoryLotModel;

class LotRepository extends BaseRepository implements LotRepositoryInterface
{
    protected function model(): string
    {
        return InventoryLotModel::class;
    }

    public function save(InventoryLot $lot): InventoryLot
    {
        if ($lot->id !== null) {
            $model = $this->newQuery()->where('tenant_id', $lot->tenantId)->findOrFail($lot->id);
        } else {
            $model = new InventoryLotModel;
        }

        $model->tenant_id = $lot->tenantId;
        $model->product_id = $lot->productId;
        $model->warehouse_id = $lot->warehouseId;
        $model->lot_number = $lot->lotNumber;
        $model->serial_number = $lot->serialNumber;
        $model->batch_number = $lot->batchNumber;
        $model->manufactured_date = $lot->manufacturedDate;
        $model->expiry_date = $lot->expiryDate;
        $model->quantity = $lot->quantity;
        $model->notes = $lot->notes;
        $model->save();

        return $this->toDomain($model);
    }

    public function findById(int $tenantId, int $id): ?InventoryLot
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, ?int $productId, ?int $warehouseId, int $page, int $perPage): array
    {
        $query = $this->newQuery()->where('tenant_id', $tenantId);

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (InventoryLotModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function delete(int $tenantId, int $id): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(InventoryLotModel $model): InventoryLot
    {
        return new InventoryLot(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            warehouseId: $model->warehouse_id,
            lotNumber: $model->lot_number,
            serialNumber: $model->serial_number,
            batchNumber: $model->batch_number,
            manufacturedDate: $model->manufactured_date?->toDateString(),
            expiryDate: $model->expiry_date?->toDateString(),
            quantity: bcadd((string) $model->quantity, '0', 4),
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
