<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\InventoryReadRepositoryContract;
use Modules\Inventory\Domain\Enums\AllocationMethod;
use Modules\Inventory\Domain\Enums\ValuationMethod;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayer;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfig;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\Item;
use Illuminate\Support\Facades\DB;

class EloquentInventoryReadRepository implements InventoryReadRepositoryContract
{
    public function resolveValuationMethod(MovementLineDTO $line): ValuationMethod
    {
        $configMethod = $this->resolveConfigMethod($line);
        if ($configMethod !== null) {
            return ValuationMethod::fromNullable($configMethod);
        }

        $itemMethod = Item::query()->whereKey($line->itemId)->value('valuation_method');

        return ValuationMethod::fromNullable($itemMethod);
    }

    public function resolveAllocationMethod(MovementLineDTO $line): AllocationMethod
    {
        $itemMethod = Item::query()->whereKey($line->itemId)->value('allocation_method');
        if (is_string($itemMethod) && $itemMethod !== '') {
            return AllocationMethod::fromNullable($itemMethod);
        }

        return match ($this->resolveValuationMethod($line)) {
            ValuationMethod::LIFO => AllocationMethod::LIFO,
            ValuationMethod::SPECIFIC_IDENTIFICATION => AllocationMethod::SERIAL,
            default => AllocationMethod::FIFO,
        };
    }

    public function findItemBaseUomId(int $itemId): int
    {
        return (int) Item::query()->whereKey($itemId)->value('base_uom_id');
    }

    public function fetchOpenLayersForUpdate(MovementLineDTO $line): Collection
    {
        $query = InventoryCostLayer::query()
            ->forTenant($line->tenantId)
            ->where('item_id', $line->itemId)
            ->where('quantity_remaining', '>', 0)
            ->where('is_closed', false)
            ->with('batch');

        if ($line->variantId !== null) {
            $query->where('variant_id', $line->variantId);
        }
        if ($line->warehouseId !== null) {
            $query->where('warehouse_id', $line->warehouseId);
        }
        if ($line->locationId !== null) {
            $query->where('location_id', $line->locationId);
        }
        if ($line->batchId !== null) {
            $query->where('batch_id', $line->batchId);
        }
        if ($line->serialId !== null) {
            $query->where('serial_id', $line->serialId);
        }

        return $query->lockForUpdate()->get();
    }

    public function findAvailableQuantityForUpdate(MovementLineDTO $line): float
    {
        $query = StockLevel::query()
            ->forTenant($line->tenantId)
            ->where('item_id', $line->itemId);

        if ($line->variantId !== null) {
            $query->where('variant_id', $line->variantId);
        }
        if ($line->warehouseId !== null) {
            $query->where('warehouse_id', $line->warehouseId);
        }
        if ($line->locationId !== null) {
            $query->where('location_id', $line->locationId);
        }
        if ($line->batchId !== null) {
            $query->where('batch_id', $line->batchId);
        }
        if ($line->serialId !== null) {
            $query->where('serial_id', $line->serialId);
        }

        $totals = $query
            ->lockForUpdate()
            ->selectRaw('COALESCE(SUM(quantity_on_hand), 0) as on_hand, COALESCE(SUM(quantity_reserved), 0) as reserved')
            ->first();

        $onHand = (float) ($totals->on_hand ?? 0.0);
        $reserved = (float) ($totals->reserved ?? 0.0);

        return round($onHand - $reserved, 4);
    }

    public function findCurrentWeightedUnitCost(MovementLineDTO $line): ?float
    {
        $query = StockLevel::query()
            ->forTenant($line->tenantId)
            ->where('item_id', $line->itemId);

        if ($line->variantId !== null) {
            $query->where('variant_id', $line->variantId);
        }
        if ($line->warehouseId !== null) {
            $query->where('warehouse_id', $line->warehouseId);
        }

        $row = $query
            ->selectRaw('SUM(quantity_on_hand * COALESCE(unit_cost, 0)) as weighted_cost, SUM(quantity_on_hand) as qty')
            ->first();

        $qty = (float) ($row->qty ?? 0.0);
        if ($qty <= 0.0) {
            return null;
        }

        return round(((float) ($row->weighted_cost ?? 0.0)) / $qty, 4);
    }

    public function findReplacementUnitCost(MovementLineDTO $line): ?float
    {
        $value = DB::table('grn_lines')
            ->where('tenant_id', $line->tenantId)
            ->where('item_id', $line->itemId)
            ->when($line->variantId !== null, fn ($q) => $q->where('variant_id', $line->variantId))
            ->orderByDesc('id')
            ->value('unit_price');

        return $value !== null ? (float) $value : null;
    }

    public function findStandardUnitCost(MovementLineDTO $line): ?float
    {
        $value = Item::query()->whereKey($line->itemId)->value('standard_cost');

        return $value !== null ? (float) $value : null;
    }

    private function resolveConfigMethod(MovementLineDTO $line): ?string
    {
        $configs = ValuationConfig::query()
            ->forTenant($line->tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($line): void {
                $q->whereNull('transaction_type')->orWhere('transaction_type', $line->txnType);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('item_id')->orWhere('item_id', $line->itemId);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('variant_id')->orWhere('variant_id', $line->variantId);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('warehouse_id')->orWhere('warehouse_id', $line->warehouseId);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('location_id')->orWhere('location_id', $line->locationId);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('batch_id')->orWhere('batch_id', $line->batchId);
            })
            ->where(function ($q) use ($line): void {
                $q->whereNull('serial_id')->orWhere('serial_id', $line->serialId);
            })
            ->get();

        if ($configs->isEmpty()) {
            return null;
        }

        return $configs
            ->sortByDesc(function (ValuationConfig $config): int {
                $score = 0;
                foreach (['item_id', 'variant_id', 'warehouse_id', 'location_id', 'batch_id', 'serial_id', 'transaction_type'] as $column) {
                    if ($config->{$column} !== null) {
                        $score++;
                    }
                }

                return $score;
            })
            ->first()?->valuation_method;
    }
}
