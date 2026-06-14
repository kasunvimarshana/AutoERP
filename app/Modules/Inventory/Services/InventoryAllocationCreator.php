<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Models\Item;

final class InventoryAllocationCreator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
        private readonly InventoryMethodResolver $methods,
        private readonly InventoryAllocationStrategyResolver $strategies,
        private readonly InventoryUomService $uoms,
        private readonly InventoryStockStateService $states,
    ) {}

    public function allocate(AllocationData $data): InventoryAllocation
    {
        $quantity = $this->math->normalize($data->quantityAllocated);
        $this->validator->assertPositiveQuantity($quantity);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $basis = $this->uoms->basis(
            $data->tenantId,
            $data->organizationUnitId,
            $item,
            $data->uomId,
            $quantity,
        );
        $quantity = $basis->baseQuantity;
        $item = $item->refresh();
        $data = $this->baseData($data, $quantity);
        $this->validateReferences($data, $quantity, $item);

        return DB::transaction(function () use ($basis, $data, $quantity, $item): InventoryAllocation {
            $reservation = $this->lockReservation($data, $quantity);
            $effectiveData = $reservation instanceof InventoryReservation
                ? $this->reservationData($data, $reservation, $quantity)
                : $data;

            if ($reservation instanceof InventoryReservation) {
                $balance = $this->balances->getOrCreateForUpdate($this->balanceData($effectiveData));
                $this->balances->releaseReserved($balance, $quantity);
            }

            $method = $this->methods->allocation(
                $item,
                $effectiveData->warehouseId,
                $effectiveData->organizationUnitId,
            );
            $plan = $this->strategies->resolve($method)->allocate($effectiveData);
            $this->assertPlanQuantity($plan, $quantity);
            $this->assertNoDuplicateSourceAllocation($effectiveData);

            $single = count($plan->lines) === 1 ? $plan->lines[0] : null;
            $allocation = InventoryAllocation::query()->create([
                'tenant_id' => $effectiveData->tenantId,
                'organization_unit_id' => $effectiveData->organizationUnitId,
                'allocation_number' => $effectiveData->allocationNumber
                    ?? $this->numbers->next($effectiveData->tenantId, 'ALC'),
                'allocation_date' => $effectiveData->allocationDate,
                'allocation_method' => $method,
                'reservation_id' => $effectiveData->reservationId,
                'item_id' => $effectiveData->itemId,
                'base_uom_id' => $basis->baseUomId,
                'entered_uom_id' => $basis->enteredUomId,
                'item_variant_id' => $effectiveData->itemVariantId,
                'warehouse_id' => $effectiveData->warehouseId,
                'warehouse_location_id' => $single?->warehouseLocationId,
                'batch_id' => $single?->batchId,
                'serial_number_id' => $single?->serialNumberId,
                'entered_quantity' => $basis->enteredQuantity,
                'conversion_factor' => $basis->conversionFactor,
                'quantity_allocated' => $quantity,
                'quantity_remaining' => $quantity,
                'source_type' => $effectiveData->sourceType,
                'source_id' => $effectiveData->sourceId,
                'source_line_type' => $effectiveData->sourceLineType,
                'source_line_id' => $effectiveData->sourceLineId,
                'status' => AllocationStatus::Active,
                'notes' => $effectiveData->notes,
                'created_by' => $effectiveData->createdBy,
            ]);

            foreach ($plan->lines as $line) {
                $balance = $this->balances->lockById($line->stockBalanceId);
                $this->balances->allocate($balance, $line->quantity);
                InventoryAllocationLine::query()->create([
                    'tenant_id' => $allocation->tenant_id,
                    'organization_unit_id' => $allocation->organization_unit_id,
                    'allocation_id' => $allocation->getKey(),
                    'stock_balance_id' => $line->stockBalanceId,
                    'batch_id' => $line->batchId,
                    'serial_number_id' => $line->serialNumberId,
                    'quantity_allocated' => $line->quantity,
                    'quantity_remaining' => $line->quantity,
                ]);
                if ($line->serialNumberId !== null) {
                    $serial = InventorySerialNumber::query()->lockForUpdate()->findOrFail($line->serialNumberId);
                    if ($serial->status !== SerialStatus::Available) {
                        throw new InvalidArgumentException('Inventory serial number is already reserved or issued.');
                    }
                    $serial->status = SerialStatus::Reserved;
                    $serial->save();
                }
                $this->states->record(
                    $balance,
                    $reservation instanceof InventoryReservation ? InventoryStockState::Reserved : InventoryStockState::Available,
                    InventoryStockState::Allocated,
                    $line->quantity,
                    $effectiveData->sourceType ?? 'inventory_allocation',
                    $effectiveData->sourceId ?? (int) $allocation->getKey(),
                    $effectiveData->sourceLineType,
                    $effectiveData->sourceLineId,
                    'Inventory allocation '.$allocation->allocation_number,
                    $effectiveData->createdBy,
                );
            }

            if ($reservation instanceof InventoryReservation) {
                $reservation->quantity_allocated = $this->math->add((string) $reservation->quantity_allocated, $quantity);
                $reservation->quantity_remaining = $this->math->sub((string) $reservation->quantity_remaining, $quantity);
                $reservation->status = $this->math->isZero((string) $reservation->quantity_remaining)
                    ? ReservationStatus::Allocated
                    : ReservationStatus::PartiallyAllocated;
                $reservation->save();
            }

            return $allocation->refresh()->load(['lines', 'issues']);
        });
    }

    public function preview(AllocationData $data): AllocationPlanData
    {
        $quantity = $this->math->normalize($data->quantityAllocated);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $quantity = $this->uoms->quantity(
            $data->tenantId,
            $data->organizationUnitId,
            $item,
            $data->uomId,
            $quantity,
        );
        $item = $item->refresh();
        $data = $this->baseData($data, $quantity);
        $this->validateReferences($data, $quantity, $item);
        $method = $this->methods->allocation($item, $data->warehouseId, $data->organizationUnitId);

        return $this->strategies->resolve($method)->preview($data);
    }

    private function validateReferences(AllocationData $data, string $quantity, Item $item): void
    {
        $item->loadMissing('category');
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        if ($data->batchId !== null) {
            $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        }
        if ($data->serialNumberId !== null) {
            $this->validator->serial(
                $item,
                $data->serialNumberId,
                $quantity,
                $data->itemVariantId,
                $data->batchId,
            );
        }
    }

    private function lockReservation(AllocationData $data, string $quantity): ?InventoryReservation
    {
        if ($data->reservationId === null) {
            return null;
        }

        $reservation = InventoryReservation::query()->lockForUpdate()->findOrFail($data->reservationId);
        if (! in_array($reservation->status, [ReservationStatus::Active, ReservationStatus::PartiallyAllocated], true)
            || $this->math->compare((string) $reservation->quantity_remaining, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory allocation cannot exceed reservation remaining quantity.');
        }
        foreach ([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_id' => $data->warehouseId,
        ] as $column => $expected) {
            if ($reservation->{$column} !== $expected) {
                throw new InvalidArgumentException('Inventory reservation does not match the allocation scope.');
            }
        }
        if ($data->warehouseLocationId !== null && $reservation->warehouse_location_id !== $data->warehouseLocationId) {
            throw new InvalidArgumentException('Inventory reservation does not match the allocation location.');
        }
        if ($data->batchId !== null && $reservation->batch_id !== $data->batchId) {
            throw new InvalidArgumentException('Inventory reservation does not match the allocation batch.');
        }

        return $reservation;
    }

    private function reservationData(
        AllocationData $data,
        InventoryReservation $reservation,
        string $quantity,
    ): AllocationData {
        return new AllocationData(
            tenantId: $data->tenantId,
            allocationDate: $data->allocationDate,
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityAllocated: $quantity,
            organizationUnitId: $data->organizationUnitId,
            allocationNumber: $data->allocationNumber,
            reservationId: $data->reservationId,
            itemVariantId: $reservation->item_variant_id,
            warehouseLocationId: $reservation->warehouse_location_id,
            batchId: $reservation->batch_id,
            serialNumberId: $data->serialNumberId,
            sourceType: $data->sourceType,
            sourceId: $data->sourceId,
            sourceLineType: $data->sourceLineType,
            sourceLineId: $data->sourceLineId,
            notes: $data->notes,
            createdBy: $data->createdBy,
        );
    }

    private function baseData(AllocationData $data, string $baseQuantity): AllocationData
    {
        return new AllocationData(
            tenantId: $data->tenantId,
            allocationDate: $data->allocationDate,
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityAllocated: $baseQuantity,
            organizationUnitId: $data->organizationUnitId,
            allocationNumber: $data->allocationNumber,
            reservationId: $data->reservationId,
            itemVariantId: $data->itemVariantId,
            warehouseLocationId: $data->warehouseLocationId,
            batchId: $data->batchId,
            serialNumberId: $data->serialNumberId,
            sourceType: $data->sourceType,
            sourceId: $data->sourceId,
            sourceLineType: $data->sourceLineType,
            sourceLineId: $data->sourceLineId,
            notes: $data->notes,
            createdBy: $data->createdBy,
        );
    }

    private function assertNoDuplicateSourceAllocation(AllocationData $data): void
    {
        if ($data->sourceType === null
            || $data->sourceId === null
            || $data->sourceLineType === null
            || $data->sourceLineId === null) {
            return;
        }

        $exists = InventoryAllocation::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->where('source_type', $data->sourceType)
            ->where('source_id', $data->sourceId)
            ->where('source_line_type', $data->sourceLineType)
            ->where('source_line_id', $data->sourceLineId)
            ->whereNotIn('status', [
                AllocationStatus::Released->value,
                AllocationStatus::Reversed->value,
                AllocationStatus::Cancelled->value,
            ])
            ->exists();
        if ($exists) {
            throw new InvalidArgumentException('An inventory allocation already exists for this source line.');
        }
    }

    private function balanceData(AllocationData $data): StockBalanceData
    {
        return new StockBalanceData(
            $data->tenantId,
            $data->itemId,
            $data->warehouseId,
            $data->organizationUnitId,
            $data->itemVariantId,
            $data->warehouseLocationId,
            $data->batchId,
        );
    }

    private function assertPlanQuantity(AllocationPlanData $plan, string $quantity): void
    {
        $planned = $this->math->sum(array_map(
            static fn ($line): string => $line->quantity,
            $plan->lines,
        ));
        if ($this->math->compare($planned, $quantity) !== 0) {
            throw new InvalidArgumentException('Inventory allocation plan does not reconcile to the requested quantity.');
        }
    }
}
