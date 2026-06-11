<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Allocation;

use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\DTOs\AllocationPlanLineData;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;

final class SerialAllocationStrategy extends AbstractAllocationStrategy
{
    protected function method(): AllocationMethod
    {
        return AllocationMethod::Serial;
    }

    protected function plan(AllocationData $data, bool $lock): AllocationPlanData
    {
        if ($this->math->compare($data->quantityAllocated, '1.000000') !== 0) {
            throw new InvalidArgumentException('Serial allocation quantity must be 1.');
        }

        $query = InventorySerialNumber::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->where('item_id', $data->itemId)
            ->where('item_variant_id', $data->itemVariantId)
            ->where('warehouse_id', $data->warehouseId)
            ->where('status', SerialStatus::Available->value);
        if ($data->warehouseLocationId !== null) {
            $query->where('warehouse_location_id', $data->warehouseLocationId);
        }
        if ($data->batchId !== null) {
            $query->where('batch_id', $data->batchId);
        }
        if ($data->serialNumberId !== null) {
            $query->whereKey($data->serialNumberId);
        }

        $serial = $lock
            ? $query->orderBy('id')->lockForUpdate()->first()
            : $query->orderBy('id')->first();
        if (! $serial instanceof InventorySerialNumber) {
            throw new InvalidArgumentException('No available serial number matches the allocation request.');
        }

        $balanceQuery = InventoryStockBalance::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->where('item_id', $data->itemId)
            ->where('item_variant_id', $data->itemVariantId)
            ->where('warehouse_id', $data->warehouseId)
            ->where('warehouse_location_id', $serial->warehouse_location_id)
            ->where('batch_id', $serial->batch_id)
            ->where('quantity_available', '>=', '1.000000');
        $balance = $lock ? $balanceQuery->lockForUpdate()->first() : $balanceQuery->first();
        if (! $balance instanceof InventoryStockBalance) {
            throw new InvalidArgumentException('The selected serial number has no available stock balance.');
        }

        return new AllocationPlanData(AllocationMethod::Serial, '1.000000', [
            new AllocationPlanLineData(
                stockBalanceId: (int) $balance->getKey(),
                quantity: '1.000000',
                warehouseLocationId: $balance->warehouse_location_id,
                batchId: $balance->batch_id,
                serialNumberId: (int) $serial->getKey(),
            ),
        ]);
    }
}
