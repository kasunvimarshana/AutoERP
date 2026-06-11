<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Allocation;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Contracts\AllocationStrategyInterface;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\DTOs\AllocationPlanLineData;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryStockBalance;

abstract class AbstractAllocationStrategy implements AllocationStrategyInterface
{
    public function __construct(protected readonly DecimalMath $math) {}

    public function allocate(AllocationData $data): AllocationPlanData
    {
        return $this->plan($data, true);
    }

    public function preview(AllocationData $data): AllocationPlanData
    {
        return $this->plan($data, false);
    }

    public function release(InventoryAllocation $allocation, string $quantity): AllocationPlanData
    {
        $remaining = $this->math->normalize($quantity);
        $lines = [];
        $query = $allocation->lines()->where('quantity_remaining', '>', 0)->orderBy('id');
        $allocationLines = $query->get();

        foreach ($allocationLines as $line) {
            if ($this->math->isZero($remaining)) {
                break;
            }
            $take = $this->math->compare((string) $line->quantity_remaining, $remaining) >= 0
                ? $remaining
                : (string) $line->quantity_remaining;
            $lines[] = $this->lineFromAllocation($line, $take);
            $remaining = $this->math->sub($remaining, $take);
        }

        if (! $this->math->isZero($remaining)) {
            throw new InvalidArgumentException('Inventory allocation release cannot exceed remaining quantity.');
        }

        return new AllocationPlanData($this->method(), $quantity, $lines);
    }

    public function reallocate(InventoryAllocation $allocation, AllocationData $data): AllocationPlanData
    {
        return $this->preview($data);
    }

    abstract protected function method(): AllocationMethod;

    protected function plan(AllocationData $data, bool $lock): AllocationPlanData
    {
        $remaining = $this->math->normalize($data->quantityAllocated);
        $lines = [];
        $query = $this->balanceQuery($data);
        $this->applyOrdering($query);
        $balances = $lock ? $query->lockForUpdate()->get() : $query->get();

        foreach ($balances as $balance) {
            if ($this->math->isZero($remaining)) {
                break;
            }
            $available = (string) $balance->quantity_available;
            if ($this->math->compare($available, '0.000000') <= 0) {
                continue;
            }
            $take = $this->math->compare($available, $remaining) >= 0 ? $remaining : $available;
            $lines[] = new AllocationPlanLineData(
                stockBalanceId: (int) $balance->getKey(),
                quantity: $take,
                warehouseLocationId: $balance->warehouse_location_id,
                batchId: $balance->batch_id,
            );
            $remaining = $this->math->sub($remaining, $take);
        }

        if (! $this->math->isZero($remaining)) {
            throw new InvalidArgumentException('Inventory allocation cannot exceed available stock.');
        }

        return new AllocationPlanData($this->method(), $data->quantityAllocated, $lines);
    }

    protected function balanceQuery(AllocationData $data): Builder
    {
        $query = InventoryStockBalance::query()
            ->where('inventory_stock_balances.tenant_id', $data->tenantId)
            ->where('inventory_stock_balances.item_id', $data->itemId)
            ->where('inventory_stock_balances.warehouse_id', $data->warehouseId)
            ->where('inventory_stock_balances.organization_unit_id', $data->organizationUnitId)
            ->where('inventory_stock_balances.item_variant_id', $data->itemVariantId)
            ->where('inventory_stock_balances.quantity_available', '>', 0);

        if ($data->warehouseLocationId !== null) {
            $query->where('inventory_stock_balances.warehouse_location_id', $data->warehouseLocationId);
        }
        if ($data->batchId !== null) {
            $query->where('inventory_stock_balances.batch_id', $data->batchId);
        }

        return $query;
    }

    protected function applyOrdering(Builder $query): void
    {
        $query->orderBy('inventory_stock_balances.id');
    }

    private function lineFromAllocation(InventoryAllocationLine $line, string $quantity): AllocationPlanLineData
    {
        return new AllocationPlanLineData(
            stockBalanceId: (int) $line->stock_balance_id,
            quantity: $quantity,
            warehouseLocationId: $line->stockBalance?->warehouse_location_id,
            batchId: $line->batch_id,
            serialNumberId: $line->serial_number_id,
        );
    }
}
