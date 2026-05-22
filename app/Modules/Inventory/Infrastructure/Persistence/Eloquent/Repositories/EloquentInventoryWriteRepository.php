<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\InventoryWriteRepositoryContract;
use Modules\Inventory\Domain\Support\Decimal;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayer;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovement;

class EloquentInventoryWriteRepository implements InventoryWriteRepositoryContract
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback, 3);
    }

    public function createMovement(
        MovementLineDTO $line,
        float $quantityIn,
        float $quantityOut,
        float $unitCost,
        float $totalCost,
        ?AllocationResultDTO $allocation = null
    ): StockMovement {
        $metadata = $line->metadata;
        if ($line->currencyId !== null) {
            $metadata['currency_id'] = $line->currencyId;
        }
        if ($line->exchangeRate !== null) {
            $metadata['exchange_rate'] = $line->exchangeRate;
        }
        if ($allocation !== null) {
            $metadata['allocation_decisions'] = array_map(
                static fn ($d): array => [
                    'layer_id' => $d->layerId,
                    'quantity' => $d->quantity,
                    'unit_cost' => $d->unitCost,
                    'batch_id' => $d->batchId,
                    'serial_id' => $d->serialId,
                    'location_id' => $d->locationId,
                ],
                $allocation->decisions,
            );
        }

        return StockMovement::query()->create([
            'tenant_id' => $line->tenantId,
            'organization_unit_id' => $line->organizationUnitId,
            'metadata' => $metadata,
            'direction' => $line->direction->value,
            'item_id' => $line->itemId,
            'variant_id' => $line->variantId,
            'batch_id' => $line->batchId,
            'serial_id' => $line->serialId,
            'location_id' => $line->locationId,
            'warehouse_id' => $line->warehouseId,
            'txn_type' => $line->txnType,
            'reference_type' => $line->referenceType,
            'reference_id' => $line->referenceId,
            'uom_id' => $line->uomId,
            'quantity' => $line->quantity,
            'quantity_in' => $quantityIn,
            'quantity_out' => $quantityOut,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'performed_by' => $line->performedBy,
            'notes' => $line->notes,
        ]);
    }

    public function applyInbound(MovementLineDTO $line, float $unitCost): void
    {
        $stockLevel = $this->findOrCreateStockLevelForUpdate($line);

        $currentQty = (float) $stockLevel->quantity_on_hand;
        $currentUnitCost = (float) ($stockLevel->unit_cost ?? 0.0);

        $newQty = Decimal::add($currentQty, $line->quantity);
        $weightedCost = $newQty <= 0.0
            ? $unitCost
            : Decimal::div(
                Decimal::add(Decimal::mul($currentQty, $currentUnitCost), Decimal::mul($line->quantity, $unitCost)),
                $newQty,
            );

        $stockLevel->quantity_on_hand = $newQty;
        $stockLevel->unit_cost = $weightedCost;
        $stockLevel->last_movement_at = now();
        $stockLevel->row_version = ((int) $stockLevel->row_version) + 1;
        $stockLevel->save();

        InventoryCostLayer::query()->create([
            'tenant_id' => $line->tenantId,
            'organization_unit_id' => $line->organizationUnitId,
            'metadata' => $line->metadata,
            'item_id' => $line->itemId,
            'variant_id' => $line->variantId,
            'batch_id' => $line->batchId,
            'serial_id' => $line->serialId,
            'warehouse_id' => $line->warehouseId,
            'location_id' => $line->locationId,
            'valuation_method' => $line->metadata['valuation_method'] ?? null,
            'layer_date' => now()->toDateString(),
            'quantity_in' => $line->quantity,
            'quantity_remaining' => $line->quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $line->referenceType,
            'reference_id' => $line->referenceId,
        ]);
    }

    public function applyOutbound(MovementLineDTO $line, AllocationResultDTO $allocation): void
    {
        foreach ($allocation->decisions as $decision) {
            $layer = InventoryCostLayer::query()->lockForUpdate()->find($decision->layerId);
            if ($layer === null) {
                continue;
            }

            $layer->quantity_remaining = Decimal::sub((float) $layer->quantity_remaining, $decision->quantity);
            if ((float) $layer->quantity_remaining <= 0.0) {
                $layer->quantity_remaining = 0;
                $layer->is_closed = true;
            }
            $layer->row_version = ((int) $layer->row_version) + 1;
            $layer->save();

            $stockLevel = StockLevel::query()
                ->forTenant($line->tenantId)
                ->where('item_id', $line->itemId)
                ->when($line->variantId !== null, fn ($q) => $q->where('variant_id', $line->variantId))
                ->when($line->warehouseId !== null, fn ($q) => $q->where('warehouse_id', $line->warehouseId))
                ->when($decision->locationId !== null, fn ($q) => $q->where('location_id', $decision->locationId))
                ->when($decision->batchId !== null, fn ($q) => $q->where('batch_id', $decision->batchId))
                ->when($decision->serialId !== null, fn ($q) => $q->where('serial_id', $decision->serialId))
                ->lockForUpdate()
                ->first();

            if ($stockLevel === null) {
                continue;
            }

            $stockLevel->quantity_on_hand = Decimal::sub((float) $stockLevel->quantity_on_hand, $decision->quantity);
            if ((float) $stockLevel->quantity_reserved > 0.0) {
                $stockLevel->quantity_reserved = Decimal::sub(
                    (float) $stockLevel->quantity_reserved,
                    Decimal::min((float) $stockLevel->quantity_reserved, $decision->quantity),
                );
            }
            $stockLevel->last_movement_at = now();
            $stockLevel->row_version = ((int) $stockLevel->row_version) + 1;
            $stockLevel->save();
        }
    }

    private function findOrCreateStockLevelForUpdate(MovementLineDTO $line): StockLevel
    {
        $query = StockLevel::query()
            ->forTenant($line->tenantId)
            ->where('item_id', $line->itemId)
            ->where('condition', 'good');

        $line->variantId !== null ? $query->where('variant_id', $line->variantId) : $query->whereNull('variant_id');
        $line->warehouseId !== null ? $query->where('warehouse_id', $line->warehouseId) : $query->whereNull('warehouse_id');
        $line->locationId !== null ? $query->where('location_id', $line->locationId) : $query->whereNull('location_id');
        $line->batchId !== null ? $query->where('batch_id', $line->batchId) : $query->whereNull('batch_id');
        $line->serialId !== null ? $query->where('serial_id', $line->serialId) : $query->whereNull('serial_id');

        $stockLevel = $query->lockForUpdate()->first();
        if ($stockLevel !== null) {
            return $stockLevel;
        }

        return StockLevel::query()->create([
            'tenant_id' => $line->tenantId,
            'organization_unit_id' => $line->organizationUnitId,
            'metadata' => $line->metadata,
            'item_id' => $line->itemId,
            'variant_id' => $line->variantId,
            'warehouse_id' => $line->warehouseId,
            'location_id' => $line->locationId,
            'batch_id' => $line->batchId,
            'serial_id' => $line->serialId,
            'uom_id' => $line->uomId,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'unit_cost' => null,
            'condition' => 'good',
            'last_movement_at' => now(),
        ]);
    }
}
