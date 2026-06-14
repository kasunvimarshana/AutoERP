<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryAllocationReleaser
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly StockBalanceService $balances,
        private readonly InventoryAllocationStrategyResolver $strategies,
        private readonly InventoryStockStateService $states,
    ) {}

    public function release(InventoryAllocation $allocation, ?string $quantity = null, ?int $releasedBy = null): InventoryAllocation
    {
        return DB::transaction(function () use ($allocation, $quantity, $releasedBy): InventoryAllocation {
            $allocation = InventoryAllocation::query()->with('lines')->lockForUpdate()->findOrFail($allocation->getKey());
            if ($allocation->status !== AllocationStatus::Active) {
                throw new InvalidArgumentException('Only active inventory allocations can be released.');
            }

            $releaseQuantity = $this->operationQuantity($quantity, (string) $allocation->quantity_remaining);
            $plan = $this->strategies->resolve($allocation->allocation_method)->release($allocation, $releaseQuantity);
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
