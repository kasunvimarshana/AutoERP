<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

class EloquentStockMovementRepository implements StockMovementRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?StockMovement
    {
        $model = StockMovementModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByProduct(string $tenantId, string $productId): array
    {
        return StockMovementModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->get()
            ->map(fn(StockMovementModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByWarehouse(string $tenantId, string $warehouseId): array
    {
        return StockMovementModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn(StockMovementModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByReference(string $tenantId, string $referenceType, string $referenceId): array
    {
        return StockMovementModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get()
            ->map(fn(StockMovementModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(StockMovement $movement): void
    {
        /** @var StockMovementModel $model */
        $model = StockMovementModel::withoutGlobalScopes()->findOrNew($movement->id);
        $model->fill([
            'tenant_id'      => $movement->tenantId,
            'product_id'     => $movement->productId,
            'variant_id'     => $movement->variantId,
            'warehouse_id'   => $movement->warehouseId,
            'location_id'    => $movement->locationId,
            'type'           => $movement->type,
            'quantity'       => $movement->quantity,
            'batch_number'   => $movement->batchNumber,
            'lot_number'     => $movement->lotNumber,
            'serial_number'  => $movement->serialNumber,
            'reference_type' => $movement->referenceType,
            'reference_id'   => $movement->referenceId,
            'notes'          => $movement->notes,
        ]);
        if (!$model->exists) {
            $model->id = $movement->id;
        }
        $model->save();
    }

    private function mapToEntity(StockMovementModel $model): StockMovement
    {
        return new StockMovement(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            productId: (string) $model->product_id,
            variantId: $model->variant_id !== null ? (string) $model->variant_id : null,
            warehouseId: (string) $model->warehouse_id,
            locationId: $model->location_id !== null ? (string) $model->location_id : null,
            type: (string) $model->type,
            quantity: (float) $model->quantity,
            batchNumber: $model->batch_number !== null ? (string) $model->batch_number : null,
            lotNumber: $model->lot_number !== null ? (string) $model->lot_number : null,
            serialNumber: $model->serial_number !== null ? (string) $model->serial_number : null,
            referenceType: $model->reference_type !== null ? (string) $model->reference_type : null,
            referenceId: $model->reference_id !== null ? (string) $model->reference_id : null,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
