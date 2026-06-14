<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySerialNumber;

final class InventoryMovementPoster
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockBalanceService $balances,
        private readonly InventoryValuationService $valuation,
        private readonly InventoryStockStateService $states,
    ) {}

    public function post(InventoryMovement $movement, ?int $postedBy = null): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $postedBy): InventoryMovement {
            $movement = InventoryMovement::query()
                ->with('item.category')
                ->lockForUpdate()
                ->findOrFail($movement->getKey());
            if ($movement->status !== InventoryStatus::Draft) {
                throw new InvalidArgumentException('Only draft inventory movements can be posted.');
            }

            $balance = $this->balances->getOrCreateForUpdate($this->balanceData($movement));
            $quantity = (string) $movement->quantity;
            if ($movement->direction === InventoryDirection::Out
                && $this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                throw new InvalidArgumentException('Inventory issue quantity cannot exceed available stock.');
            }

            $valuation = $movement->reversal_of_id === null
                ? ($movement->direction === InventoryDirection::In
                    ? $this->valuation->receive($movement)
                    : $this->valuation->issue($movement, $quantity))
                : $this->valuation->reverse(
                    InventoryMovement::query()->lockForUpdate()->findOrFail($movement->reversal_of_id),
                    $movement,
                );

            $movement->unit_cost = $valuation->unitCost;
            $movement->total_cost = $valuation->totalCost;
            if ($movement->direction === InventoryDirection::In) {
                $this->balances->increaseByValue($balance, $quantity, $valuation->totalCost);
            } else {
                $this->balances->decreaseByValue($balance, $quantity, $valuation->totalCost);
            }

            $this->updateSerial($movement);
            $balance->refresh();
            $movement->balance_quantity_after = $balance->quantity_on_hand;
            $movement->balance_value_after = $balance->total_value;
            $movement->status = InventoryStatus::Posted;
            $movement->posted_by = $postedBy;
            $movement->posted_at = now();
            $movement->save();
            $this->states->record(
                $movement,
                $movement->from_state,
                $movement->to_state,
                $quantity,
                $movement->source_type,
                $movement->source_id,
                $movement->source_line_type,
                $movement->source_line_id,
                $movement->description,
                $postedBy ?? $movement->created_by,
            );

            return $movement->refresh();
        });
    }

    private function balanceData(InventoryMovement $movement): StockBalanceData
    {
        return new StockBalanceData(
            tenantId: (int) $movement->tenant_id,
            itemId: (int) $movement->item_id,
            warehouseId: (int) $movement->warehouse_id,
            organizationUnitId: $movement->organization_unit_id,
            itemVariantId: $movement->item_variant_id,
            warehouseLocationId: $movement->warehouse_location_id,
            batchId: $movement->batch_id,
        );
    }

    private function updateSerial(InventoryMovement $movement): void
    {
        if ($movement->serial_number_id === null) {
            return;
        }

        $serial = InventorySerialNumber::query()->lockForUpdate()->findOrFail($movement->serial_number_id);
        $latestMovement = InventoryMovement::query()
            ->where('serial_number_id', $serial->getKey())
            ->where('id', '!=', $movement->getKey())
            ->where('status', InventoryStatus::Posted->value)
            ->latest('id')
            ->first();

        if ($movement->direction === InventoryDirection::In) {
            if ($serial->status === SerialStatus::Reserved
                || ($serial->status === SerialStatus::Available
                    && $latestMovement?->direction === InventoryDirection::In)) {
                throw new InvalidArgumentException('Inventory serial number is already available in stock.');
            }

            $serial->status = SerialStatus::Available;
            $serial->warehouse_id = $movement->warehouse_id;
            $serial->warehouse_location_id = $movement->warehouse_location_id;
            $serial->batch_id = $movement->batch_id;
        } else {
            if (! in_array($serial->status, [SerialStatus::Available, SerialStatus::Reserved], true)) {
                throw new InvalidArgumentException('Inventory serial number is not available for issue.');
            }
            if (! $latestMovement instanceof InventoryMovement
                || $latestMovement->direction !== InventoryDirection::In) {
                throw new InvalidArgumentException('Inventory serial number has no available receipt to issue.');
            }
            if ((int) $serial->warehouse_id !== (int) $movement->warehouse_id
                || $serial->warehouse_location_id !== $movement->warehouse_location_id
                || $serial->batch_id !== $movement->batch_id) {
                throw new InvalidArgumentException('Inventory serial number does not match the issue stock location.');
            }
            $serial->status = $movement->reversal_of_id === null ? SerialStatus::Issued : SerialStatus::Returned;
        }
        $serial->save();
    }
}
