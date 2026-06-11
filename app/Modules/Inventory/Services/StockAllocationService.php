<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
        private readonly StockMovementService $movements,
    ) {}

    public function allocate(AllocationData $data): InventoryAllocation
    {
        $quantity = $this->math->normalize($data->quantityAllocated);
        $this->validator->assertPositiveQuantity($quantity);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId);
        $this->validator->serial($item, $data->serialNumberId, $quantity);

        return DB::transaction(function () use ($data, $quantity, $item): InventoryAllocation {
            $reservation = $data->reservationId === null ? null : InventoryReservation::query()->findOrFail($data->reservationId);
            if ($reservation !== null && $this->math->compare((string) $reservation->quantity_remaining, $quantity) < 0) {
                throw new InvalidArgumentException('Inventory allocation cannot exceed reservation remaining quantity.');
            }

            $balance = $this->balances->getOrCreate($this->balanceData($data));
            if ($reservation === null && $this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                throw new InvalidArgumentException('Inventory allocation cannot exceed available stock.');
            }

            if ($reservation !== null) {
                $reservation->quantity_allocated = $this->math->add((string) $reservation->quantity_allocated, $quantity);
                $reservation->quantity_remaining = $this->math->sub((string) $reservation->quantity_remaining, $quantity);
                $reservation->status = $this->math->isZero((string) $reservation->quantity_remaining)
                    ? ReservationStatus::Allocated
                    : ReservationStatus::PartiallyAllocated;
                $reservation->save();
                $this->balances->releaseReserved($balance, $quantity);
            }

            $this->balances->allocate($balance, $quantity);

            return InventoryAllocation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'allocation_number' => $data->allocationNumber ?? $this->numbers->next($data->tenantId, 'ALC', 'inventory_allocations', 'allocation_number'),
                'allocation_date' => $data->allocationDate,
                'reservation_id' => $data->reservationId,
                'item_id' => $data->itemId,
                'base_uom_id' => $item->base_uom_id,
                'item_variant_id' => $data->itemVariantId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'batch_id' => $data->batchId,
                'serial_number_id' => $data->serialNumberId,
                'quantity_allocated' => $quantity,
                'quantity_remaining' => $quantity,
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'source_line_type' => $data->sourceLineType,
                'source_line_id' => $data->sourceLineId,
                'status' => AllocationStatus::Active,
                'notes' => $data->notes,
            ]);
        });
    }

    public function issue(InventoryAllocation $allocation): InventoryAllocation
    {
        if ($allocation->status !== AllocationStatus::Active) {
            throw new InvalidArgumentException('Only active inventory allocations can be issued.');
        }

        return DB::transaction(function () use ($allocation): InventoryAllocation {
            $balance = $this->balances->getOrCreate($this->balanceDataFromAllocation($allocation));
            $this->balances->releaseAllocated($balance, (string) $allocation->quantity_remaining);

            $this->movements->record(new StockMovementData(
                tenantId: (int) $allocation->tenant_id,
                movementDate: now()->toDateString(),
                movementType: InventoryMovementType::Issue,
                direction: InventoryDirection::Out,
                itemId: (int) $allocation->item_id,
                warehouseId: (int) $allocation->warehouse_id,
                quantity: (string) $allocation->quantity_remaining,
                organizationUnitId: $allocation->organization_unit_id,
                itemVariantId: $allocation->item_variant_id,
                warehouseLocationId: $allocation->warehouse_location_id,
                batchId: $allocation->batch_id,
                serialNumberId: $allocation->serial_number_id,
                sourceType: $allocation->source_type,
                sourceId: $allocation->source_id,
                sourceLineType: $allocation->source_line_type,
                sourceLineId: $allocation->source_line_id,
                description: 'Issue from inventory allocation '.$allocation->allocation_number,
            ));

            $allocation->quantity_issued = $this->math->add((string) $allocation->quantity_issued, (string) $allocation->quantity_remaining);
            $allocation->quantity_remaining = '0.000000';
            $allocation->status = AllocationStatus::Issued;
            $allocation->save();

            return $allocation->refresh();
        });
    }

    public function release(InventoryAllocation $allocation): InventoryAllocation
    {
        if ($allocation->status !== AllocationStatus::Active) {
            throw new InvalidArgumentException('Only active inventory allocations can be released.');
        }

        return DB::transaction(function () use ($allocation): InventoryAllocation {
            $balance = $this->balances->getOrCreate($this->balanceDataFromAllocation($allocation));
            $this->balances->releaseAllocated($balance, (string) $allocation->quantity_remaining);
            $allocation->quantity_released = $this->math->add((string) $allocation->quantity_released, (string) $allocation->quantity_remaining);
            $allocation->quantity_remaining = '0.000000';
            $allocation->status = AllocationStatus::Released;
            $allocation->save();

            return $allocation->refresh();
        });
    }

    private function balanceData(AllocationData $data): StockBalanceData
    {
        return new StockBalanceData($data->tenantId, $data->itemId, $data->warehouseId, $data->organizationUnitId, $data->itemVariantId, $data->warehouseLocationId, $data->batchId);
    }

    private function balanceDataFromAllocation(InventoryAllocation $allocation): StockBalanceData
    {
        return new StockBalanceData((int) $allocation->tenant_id, (int) $allocation->item_id, (int) $allocation->warehouse_id, $allocation->organization_unit_id, $allocation->item_variant_id, $allocation->warehouse_location_id, $allocation->batch_id);
    }
}
