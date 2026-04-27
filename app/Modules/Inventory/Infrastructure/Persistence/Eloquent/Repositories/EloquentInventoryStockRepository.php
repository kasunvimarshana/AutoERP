<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

class EloquentInventoryStockRepository implements InventoryStockRepositoryInterface
{
    public function __construct(private readonly StockMovementModel $stockMovementModel) {}

    public function recordMovement(StockMovement $movement): StockMovement
    {
        /** @var StockMovementModel $saved */
        $saved = $this->stockMovementModel->newQuery()->create([
            'tenant_id' => $movement->getTenantId(),
            'product_id' => $movement->getProductId(),
            'variant_id' => $movement->getVariantId(),
            'batch_id' => $movement->getBatchId(),
            'serial_id' => $movement->getSerialId(),
            'from_location_id' => $movement->getFromLocationId(),
            'to_location_id' => $movement->getToLocationId(),
            'movement_type' => $movement->getMovementType(),
            'reference_type' => $movement->getReferenceType(),
            'reference_id' => $movement->getReferenceId(),
            'uom_id' => $movement->getUomId(),
            'quantity' => $movement->getQuantity(),
            'unit_cost' => $movement->getUnitCost(),
            'performed_by' => $movement->getPerformedBy(),
            'performed_at' => $movement->getPerformedAt() ?? now(),
            'notes' => $movement->getNotes(),
            'metadata' => $movement->getMetadata(),
        ]);

        return $this->mapToEntity($saved);
    }

    public function adjustStockLevel(StockMovement $movement): void
    {
        $movementType = $movement->getMovementType();
        $qty = (string) $movement->getQuantity();

        if (in_array($movementType, ['shipment', 'transfer', 'adjustment_out', 'return_out', 'write_off'], true) && $movement->getFromLocationId() !== null) {
            $this->applyStockDelta($movement, $movement->getFromLocationId(), $qty, '-');
        }

        if (in_array($movementType, ['receipt', 'transfer', 'adjustment_in', 'return_in', 'opening'], true) && $movement->getToLocationId() !== null) {
            $this->applyStockDelta($movement, $movement->getToLocationId(), $qty, '+');
        }
    }

    public function paginateByWarehouse(
        int $tenantId,
        int $warehouseId,
        array $filters,
        int $perPage,
        int $page,
        ?string $sort = null,
    ): mixed {
        $query = $this->stockMovementModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($warehouseId): void {
                $builder->whereIn('from_location_id', function ($sub) use ($warehouseId): void {
                    $sub->select('id')->from('warehouse_locations')->where('warehouse_id', $warehouseId);
                })->orWhereIn('to_location_id', function ($sub) use ($warehouseId): void {
                    $sub->select('id')->from('warehouse_locations')->where('warehouse_id', $warehouseId);
                });
            });

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->where($field, $value);
        }

        if ($sort !== null && $sort !== '') {
            $parts = explode(':', $sort, 2);
            $column = $parts[0];
            $direction = strtolower($parts[1] ?? 'desc');
            if (! in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'desc';
            }
            if (in_array($column, ['id', 'movement_type', 'product_id', 'performed_at', 'created_at'], true)) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderByDesc('performed_at')->orderByDesc('id');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginateStockLevelsByWarehouse(int $tenantId, int $warehouseId, int $perPage, int $page): mixed
    {
        return DB::table('stock_levels')
            ->join('warehouse_locations', 'warehouse_locations.id', '=', 'stock_levels.location_id')
            ->where('stock_levels.tenant_id', $tenantId)
            ->where('warehouse_locations.warehouse_id', $warehouseId)
            ->select('stock_levels.*')
            ->orderBy('stock_levels.product_id')
            ->orderBy('stock_levels.location_id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function locationBelongsToWarehouse(int $tenantId, int $warehouseId, int $locationId): bool
    {
        return DB::table('warehouse_locations')
            ->where('id', $locationId)
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->exists();
    }

    public function warehouseExists(int $tenantId, int $warehouseId): bool
    {
        return DB::table('warehouses')
            ->where('id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    private function applyStockDelta(StockMovement $movement, int $locationId, string $qty, string $operation): void
    {
        $existing = DB::table('stock_levels')
            ->where('tenant_id', $movement->getTenantId())
            ->where('product_id', $movement->getProductId())
            ->where('variant_id', $movement->getVariantId())
            ->where('location_id', $locationId)
            ->where('batch_id', $movement->getBatchId())
            ->where('serial_id', $movement->getSerialId())
            ->first();

        if ($existing === null) {
            DB::table('stock_levels')->insert([
                'tenant_id' => $movement->getTenantId(),
                'product_id' => $movement->getProductId(),
                'variant_id' => $movement->getVariantId(),
                'location_id' => $locationId,
                'batch_id' => $movement->getBatchId(),
                'serial_id' => $movement->getSerialId(),
                'uom_id' => $movement->getUomId(),
                'quantity_on_hand' => $operation === '-' ? bcmul($qty, '-1', 6) : $qty,
                'quantity_reserved' => '0.000000',
                'unit_cost' => $movement->getUnitCost(),
                'last_movement_at' => $movement->getPerformedAt() ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $current = (string) $existing->quantity_on_hand;
        $updatedQty = $operation === '-' ? bcsub($current, $qty, 6) : bcadd($current, $qty, 6);

        DB::table('stock_levels')
            ->where('id', $existing->id)
            ->update([
                'quantity_on_hand' => $updatedQty,
                'unit_cost' => $movement->getUnitCost() ?? $existing->unit_cost,
                'last_movement_at' => $movement->getPerformedAt() ?? now(),
                'updated_at' => now(),
            ]);
    }

    private function mapToEntity(StockMovementModel $model): StockMovement
    {
        return new StockMovement(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            batchId: $model->batch_id !== null ? (int) $model->batch_id : null,
            serialId: $model->serial_id !== null ? (int) $model->serial_id : null,
            fromLocationId: $model->from_location_id !== null ? (int) $model->from_location_id : null,
            toLocationId: $model->to_location_id !== null ? (int) $model->to_location_id : null,
            movementType: (string) $model->movement_type,
            referenceType: $model->reference_type,
            referenceId: $model->reference_id !== null ? (int) $model->reference_id : null,
            uomId: (int) $model->uom_id,
            quantity: (string) $model->quantity,
            unitCost: $model->unit_cost !== null ? (string) $model->unit_cost : null,
            performedBy: $model->performed_by !== null ? (int) $model->performed_by : null,
            performedAt: $model->performed_at,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
        );
    }
}
