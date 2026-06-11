<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Contracts\AllocationStrategyInterface;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Models\Item;

final class InventoryAllocationService
{
    public function __construct(
        private readonly Container $container,
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
        private readonly StockMovementService $movements,
        private readonly InventoryMethodResolver $methods,
        private readonly InventoryUomService $uoms,
        private readonly InventoryStockStateService $states,
    ) {}

    public function allocate(AllocationData $data): InventoryAllocation
    {
        $quantity = $this->math->normalize($data->quantityAllocated);
        $this->validator->assertPositiveQuantity($quantity);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        if ($data->uomId !== null) {
            $quantity = $this->uoms->quantity($data->tenantId, $data->organizationUnitId, $item, $data->uomId, $quantity);
            $item = $item->refresh();
            $data = new AllocationData(
                tenantId: $data->tenantId,
                allocationDate: $data->allocationDate,
                itemId: $data->itemId,
                warehouseId: $data->warehouseId,
                quantityAllocated: $quantity,
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
        $this->validateReferences($data, $quantity, $item);

        return DB::transaction(function () use ($data, $quantity, $item): InventoryAllocation {
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
            $plan = $this->strategy($method)->allocate($effectiveData);
            $this->assertPlanQuantity($plan, $quantity);

            $single = count($plan->lines) === 1 ? $plan->lines[0] : null;
            $allocation = InventoryAllocation::query()->create([
                'tenant_id' => $effectiveData->tenantId,
                'organization_unit_id' => $effectiveData->organizationUnitId,
                'allocation_number' => $effectiveData->allocationNumber
                    ?? $this->numbers->next($effectiveData->tenantId, 'ALC', 'inventory_allocations', 'allocation_number'),
                'allocation_date' => $effectiveData->allocationDate,
                'allocation_method' => $method,
                'reservation_id' => $effectiveData->reservationId,
                'item_id' => $effectiveData->itemId,
                'base_uom_id' => $item->base_uom_id,
                'item_variant_id' => $effectiveData->itemVariantId,
                'warehouse_id' => $effectiveData->warehouseId,
                'warehouse_location_id' => $single?->warehouseLocationId,
                'batch_id' => $single?->batchId,
                'serial_number_id' => $single?->serialNumberId,
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

    public function issue(InventoryAllocation $allocation, ?string $quantity = null, ?int $issuedBy = null): InventoryAllocation
    {
        return DB::transaction(function () use ($allocation, $quantity, $issuedBy): InventoryAllocation {
            $allocation = InventoryAllocation::query()
                ->with('lines.stockBalance')
                ->lockForUpdate()
                ->findOrFail($allocation->getKey());
            if ($allocation->status !== AllocationStatus::Active) {
                throw new InvalidArgumentException('Only active inventory allocations can be issued.');
            }

            $issueQuantity = $this->operationQuantity($quantity, (string) $allocation->quantity_remaining);
            $remaining = $issueQuantity;
            foreach ($allocation->lines as $line) {
                if ($this->math->isZero($remaining)) {
                    break;
                }
                if ($this->math->isZero((string) $line->quantity_remaining)) {
                    continue;
                }

                $take = $this->math->compare((string) $line->quantity_remaining, $remaining) >= 0
                    ? $remaining
                    : (string) $line->quantity_remaining;
                $balance = $this->balances->lockById((int) $line->stock_balance_id);
                $this->balances->releaseAllocated($balance, $take);
                $movement = $this->movements->record(new StockMovementData(
                    tenantId: (int) $allocation->tenant_id,
                    movementDate: now()->toDateString(),
                    movementType: InventoryMovementType::Issue,
                    direction: InventoryDirection::Out,
                    itemId: (int) $allocation->item_id,
                    warehouseId: (int) $allocation->warehouse_id,
                    quantity: $take,
                    organizationUnitId: $allocation->organization_unit_id,
                    itemVariantId: $allocation->item_variant_id,
                    warehouseLocationId: $balance->warehouse_location_id,
                    batchId: $line->batch_id,
                    serialNumberId: $line->serial_number_id,
                    sourceType: $allocation->source_type ?? 'inventory_allocation',
                    sourceId: $allocation->source_id ?? (int) $allocation->getKey(),
                    sourceLineType: $allocation->source_line_type ?? 'inventory_allocation_line',
                    sourceLineId: $allocation->source_line_id ?? (int) $line->getKey(),
                    description: 'Issue from inventory allocation '.$allocation->allocation_number,
                    createdBy: $issuedBy,
                    fromState: InventoryStockState::Allocated,
                    toState: InventoryStockState::Issued,
                ));

                InventoryAllocationIssue::query()->create([
                    'tenant_id' => $allocation->tenant_id,
                    'organization_unit_id' => $allocation->organization_unit_id,
                    'allocation_id' => $allocation->getKey(),
                    'allocation_line_id' => $line->getKey(),
                    'movement_id' => $movement->getKey(),
                    'quantity_issued' => $take,
                    'unit_cost' => $movement->unit_cost,
                    'total_cost' => $movement->total_cost,
                ]);

                $line->quantity_issued = $this->math->add((string) $line->quantity_issued, $take);
                $line->quantity_remaining = $this->math->sub((string) $line->quantity_remaining, $take);
                $line->save();
                $remaining = $this->math->sub($remaining, $take);
            }

            if (! $this->math->isZero($remaining)) {
                throw new InvalidArgumentException('Inventory allocation issue could not be reconciled to allocation lines.');
            }

            $allocation->quantity_issued = $this->math->add((string) $allocation->quantity_issued, $issueQuantity);
            $allocation->quantity_remaining = $this->math->sub((string) $allocation->quantity_remaining, $issueQuantity);
            if ($this->math->isZero((string) $allocation->quantity_remaining)) {
                $allocation->status = AllocationStatus::Issued;
                $allocation->issued_by = $issuedBy;
                $allocation->issued_at = now();
            }
            $allocation->save();

            return $allocation->refresh()->load(['lines', 'issues.movement']);
        });
    }

    public function release(InventoryAllocation $allocation, ?string $quantity = null, ?int $releasedBy = null): InventoryAllocation
    {
        return DB::transaction(function () use ($allocation, $quantity, $releasedBy): InventoryAllocation {
            $allocation = InventoryAllocation::query()->with('lines')->lockForUpdate()->findOrFail($allocation->getKey());
            if ($allocation->status !== AllocationStatus::Active) {
                throw new InvalidArgumentException('Only active inventory allocations can be released.');
            }

            $releaseQuantity = $this->operationQuantity($quantity, (string) $allocation->quantity_remaining);
            $plan = $this->strategy($allocation->allocation_method)->release($allocation, $releaseQuantity);
            foreach ($plan->lines as $planLine) {
                $line = InventoryAllocationLine::query()
                    ->where('allocation_id', $allocation->getKey())
                    ->where('stock_balance_id', $planLine->stockBalanceId)
                    ->where('batch_id', $planLine->batchId)
                    ->where('serial_number_id', $planLine->serialNumberId)
                    ->where('quantity_remaining', '>', 0)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->firstOrFail();
                $balance = $this->balances->lockById($planLine->stockBalanceId);
                $this->balances->releaseAllocated($balance, $planLine->quantity);
                $line->quantity_released = $this->math->add((string) $line->quantity_released, $planLine->quantity);
                $line->quantity_remaining = $this->math->sub((string) $line->quantity_remaining, $planLine->quantity);
                $line->save();

                if ($line->serial_number_id !== null) {
                    $serial = InventorySerialNumber::query()->lockForUpdate()->findOrFail($line->serial_number_id);
                    if ($serial->status === SerialStatus::Reserved) {
                        $serial->status = SerialStatus::Available;
                        $serial->save();
                    }
                }
                $this->states->record(
                    $balance,
                    InventoryStockState::Allocated,
                    InventoryStockState::Available,
                    $planLine->quantity,
                    $allocation->source_type ?? 'inventory_allocation',
                    $allocation->source_id ?? (int) $allocation->getKey(),
                    $allocation->source_line_type,
                    $allocation->source_line_id,
                    'Inventory allocation release '.$allocation->allocation_number,
                    $releasedBy,
                );
            }

            $allocation->quantity_released = $this->math->add((string) $allocation->quantity_released, $releaseQuantity);
            $allocation->quantity_remaining = $this->math->sub((string) $allocation->quantity_remaining, $releaseQuantity);
            if ($this->math->isZero((string) $allocation->quantity_remaining)) {
                $allocation->status = AllocationStatus::Released;
                $allocation->released_by = $releasedBy;
                $allocation->released_at = now();
            }
            $allocation->save();

            return $allocation->refresh()->load(['lines', 'issues']);
        });
    }

    public function reallocate(InventoryAllocation $allocation, AllocationData $data): InventoryAllocation
    {
        return DB::transaction(function () use ($allocation, $data): InventoryAllocation {
            $this->release($allocation);

            return $this->allocate($data);
        });
    }

    public function preview(AllocationData $data): AllocationPlanData
    {
        $quantity = $this->math->normalize($data->quantityAllocated);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        if ($data->uomId !== null) {
            $quantity = $this->uoms->quantity($data->tenantId, $data->organizationUnitId, $item, $data->uomId, $quantity);
            $item = $item->refresh();
            $data = new AllocationData(
                tenantId: $data->tenantId,
                allocationDate: $data->allocationDate,
                itemId: $data->itemId,
                warehouseId: $data->warehouseId,
                quantityAllocated: $quantity,
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
        $this->validateReferences($data, $quantity, $item);
        $method = $this->methods->allocation($item, $data->warehouseId, $data->organizationUnitId);

        return $this->strategy($method)->preview($data);
    }

    private function validateReferences(AllocationData $data, string $quantity, Item $item): void
    {
        $item->loadMissing('category');
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        if ($data->batchId !== null) {
            $this->validator->batch($item, $data->batchId);
        }
        if ($data->serialNumberId !== null) {
            $this->validator->serial($item, $data->serialNumberId, $quantity);
        }

        return;
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

    private function operationQuantity(?string $quantity, string $remaining): string
    {
        $operationQuantity = $this->math->normalize($quantity ?? $remaining);
        $this->validator->assertPositiveQuantity($operationQuantity);
        if ($this->math->compare($operationQuantity, $remaining) > 0) {
            throw new InvalidArgumentException('Inventory operation quantity cannot exceed remaining allocation quantity.');
        }

        return $operationQuantity;
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

    private function strategy(AllocationMethod $method): AllocationStrategyInterface
    {
        $class = config('inventory.allocation.strategies.'.$method->value);
        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException("Inventory allocation strategy [{$method->value}] is not configured.");
        }

        $strategy = $this->container->make($class);
        if (! $strategy instanceof AllocationStrategyInterface) {
            throw new InvalidArgumentException("Inventory allocation strategy [{$class}] is invalid.");
        }

        return $strategy;
    }
}
