<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryAllocationIssuer
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly StockBalanceService $balances,
        private readonly StockMovementService $movements,
    ) {}

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

    private function operationQuantity(?string $quantity, string $remaining): string
    {
        $operationQuantity = $this->math->normalize($quantity ?? $remaining);
        $this->validator->assertPositiveQuantity($operationQuantity);
        if ($this->math->compare($operationQuantity, $remaining) > 0) {
            throw new InvalidArgumentException('Inventory operation quantity cannot exceed remaining allocation quantity.');
        }

        return $operationQuantity;
    }
}
