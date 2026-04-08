<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\StockLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\StockLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;

class EloquentStockLevelRepository implements StockLevelRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?StockLevel
    {
        $model = StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByProduct(string $tenantId, string $productId, ?string $variantId = null): array
    {
        $query = StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        return $query->get()
            ->map(fn(StockLevelModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByWarehouse(string $tenantId, string $warehouseId): array
    {
        return StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn(StockLevelModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByLocation(string $tenantId, string $locationId): array
    {
        return StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('location_id', $locationId)
            ->get()
            ->map(fn(StockLevelModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByBatch(string $tenantId, string $batchNumber): array
    {
        return StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('batch_number', $batchNumber)
            ->get()
            ->map(fn(StockLevelModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(StockLevel $stockLevel): void
    {
        /** @var StockLevelModel $model */
        $model = StockLevelModel::withoutGlobalScopes()->findOrNew($stockLevel->id);
        $model->fill([
            'tenant_id'         => $stockLevel->tenantId,
            'product_id'        => $stockLevel->productId,
            'variant_id'        => $stockLevel->variantId,
            'warehouse_id'      => $stockLevel->warehouseId,
            'location_id'       => $stockLevel->locationId,
            'batch_number'      => $stockLevel->batchNumber,
            'lot_number'        => $stockLevel->lotNumber,
            'serial_number'     => $stockLevel->serialNumber,
            'quantity'          => $stockLevel->quantity,
            'reserved_quantity' => $stockLevel->reservedQuantity,
            'expiry_date'       => $stockLevel->expiryDate?->format('Y-m-d'),
        ]);
        if (!$model->exists) {
            $model->id = $stockLevel->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        StockLevelModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(StockLevelModel $model): StockLevel
    {
        return new StockLevel(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            productId: (string) $model->product_id,
            variantId: $model->variant_id !== null ? (string) $model->variant_id : null,
            warehouseId: (string) $model->warehouse_id,
            locationId: $model->location_id !== null ? (string) $model->location_id : null,
            batchNumber: $model->batch_number !== null ? (string) $model->batch_number : null,
            lotNumber: $model->lot_number !== null ? (string) $model->lot_number : null,
            serialNumber: $model->serial_number !== null ? (string) $model->serial_number : null,
            quantity: (float) $model->quantity,
            reservedQuantity: (float) $model->reserved_quantity,
            expiryDate: $model->expiry_date !== null
                ? new \DateTimeImmutable($model->expiry_date->toDateString())
                : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
