<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockPostingResult;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\ItemBaseUomRevisionStatus;
use Modules\Item\Models\ItemBaseUomRevision;

final class StockMovementService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
        private readonly InventoryValuationService $valuation,
        private readonly InventoryUomService $uoms,
        private readonly InventoryStockStateService $states,
    ) {}

    public function create(StockMovementData $data): InventoryMovement
    {
        $quantity = $this->math->normalize($data->quantity);
        $unitCost = $this->math->normalize($data->unitCost);
        $this->validator->assertPositiveQuantity($quantity);
        $this->validator->assertNonNegative($unitCost, 'Inventory unit cost cannot be negative.');

        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        if ($data->uomId !== null) {
            $basis = $this->uoms->basis($data->tenantId, $data->organizationUnitId, $item, $data->uomId, $quantity, $unitCost);
            $quantity = $basis['quantity'];
            $unitCost = $basis['unit_cost'];
            $item = $item->refresh();
        }
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        $this->validator->serial(
            $item,
            $data->serialNumberId,
            $quantity,
            $data->itemVariantId,
            $data->batchId,
        );

        return InventoryMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'movement_number' => $data->movementNumber ?? $this->numbers->next($data->tenantId, 'MOV', 'inventory_movements', 'movement_number'),
            'movement_date' => $data->movementDate,
            'movement_type' => $data->movementType,
            'direction' => $data->direction,
            'item_id' => $data->itemId,
            'base_uom_id' => $item->base_uom_id,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_id' => $data->warehouseId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $this->math->mul($quantity, $unitCost),
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'source_line_type' => $data->sourceLineType,
            'source_line_id' => $data->sourceLineId,
            'from_state' => $data->fromState ?? $this->defaultFromState($data->direction),
            'to_state' => $data->toState ?? $this->defaultToState($data->direction),
            'status' => InventoryStatus::Draft,
            'description' => $data->description,
            'created_by' => $data->createdBy,
        ]);
    }

    public function record(StockMovementData $data, ?int $postedBy = null): InventoryMovement
    {
        return DB::transaction(fn (): InventoryMovement => $this->post($this->create($data), $postedBy));
    }

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
            } elseif ($movement->direction === InventoryDirection::Out) {
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

    public function reverse(InventoryMovement $movement, ?int $reversedBy = null): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $reversedBy): InventoryMovement {
            $movement = InventoryMovement::query()->lockForUpdate()->findOrFail($movement->getKey());
            if ($movement->status !== InventoryStatus::Posted) {
                throw new InvalidArgumentException('Only posted inventory movements can be reversed.');
            }
            if ($movement->reversals()->whereIn('status', [
                InventoryStatus::Draft->value,
                InventoryStatus::Posted->value,
            ])->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('Inventory movement already has a reversal.');
            }

            $direction = $movement->direction === InventoryDirection::In ? InventoryDirection::Out : InventoryDirection::In;
            $type = $direction === InventoryDirection::In ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut;
            [$reversalQuantity, $reversalUnitCost] = $this->reversalBasis($movement);
            $reversal = $this->create(new StockMovementData(
                tenantId: (int) $movement->tenant_id,
                movementDate: now()->toDateString(),
                movementType: $type,
                direction: $direction,
                itemId: (int) $movement->item_id,
                warehouseId: (int) $movement->warehouse_id,
                quantity: $reversalQuantity,
                organizationUnitId: $movement->organization_unit_id,
                itemVariantId: $movement->item_variant_id,
                warehouseLocationId: $movement->warehouse_location_id,
                batchId: $movement->batch_id,
                serialNumberId: $movement->serial_number_id,
                unitCost: $reversalUnitCost,
                sourceType: 'inventory_movement',
                sourceId: (int) $movement->getKey(),
                description: 'Reversal of '.$movement->movement_number,
                fromState: $movement->to_state,
                toState: InventoryStockState::Reversed,
            ));
            $reversal->reversal_of_id = $movement->getKey();
            $reversal->save();
            $reversal = $this->post($reversal, $reversedBy);

            $movement->status = InventoryStatus::Reversed;
            $movement->reversed_by = $reversedBy;
            $movement->reversed_at = now();
            $movement->save();
            $this->markAllocationIssueReversed($movement, $reversal);

            return $reversal->refresh();
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function reversalBasis(InventoryMovement $movement): array
    {
        $item = $movement->item()->firstOrFail();
        $fromUomId = (int) ($movement->base_uom_id ?: $item->base_uom_id);
        $toUomId = (int) $item->base_uom_id;
        if ($fromUomId === $toUomId) {
            return [(string) $movement->quantity, (string) $movement->unit_cost];
        }

        $factor = '1.000000';
        $currentUomId = $fromUomId;
        $revisions = ItemBaseUomRevision::query()
            ->where('tenant_id', $movement->tenant_id)
            ->where('item_id', $movement->item_id)
            ->where('status', ItemBaseUomRevisionStatus::Applied->value)
            ->where('applied_at', '>=', $movement->created_at)
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();
        foreach ($revisions as $revision) {
            if ((int) $revision->old_base_uom_id !== $currentUomId) {
                continue;
            }
            $factor = $this->math->mul($factor, (string) $revision->conversion_factor);
            $currentUomId = (int) $revision->new_base_uom_id;
            if ($currentUomId === $toUomId) {
                return [
                    $this->math->mul((string) $movement->quantity, $factor),
                    $this->math->div((string) $movement->unit_cost, $factor),
                ];
            }
        }

        throw new InvalidArgumentException('Historical inventory movement UOM cannot be converted to the current item base UOM.');
    }

    public function result(InventoryMovement $movement): StockPostingResult
    {
        return new StockPostingResult(
            movementId: (int) $movement->getKey(),
            movementNumber: (string) $movement->movement_number,
            status: $movement->status instanceof InventoryStatus ? $movement->status->value : (string) $movement->status,
            quantity: (string) $movement->quantity,
            unitCost: (string) $movement->unit_cost,
            totalCost: (string) $movement->total_cost,
            balanceQuantityAfter: (string) $movement->balance_quantity_after,
            balanceValueAfter: (string) $movement->balance_value_after,
        );
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

    private function markAllocationIssueReversed(InventoryMovement $movement, InventoryMovement $reversal): void
    {
        $issue = InventoryAllocationIssue::query()
            ->where('movement_id', $movement->getKey())
            ->lockForUpdate()
            ->first();
        if (! $issue instanceof InventoryAllocationIssue) {
            return;
        }

        $issue->reversal_movement_id = $reversal->getKey();
        $issue->reversed_at = now();
        $issue->save();

        $line = InventoryAllocationLine::query()->lockForUpdate()->findOrFail($issue->allocation_line_id);
        $line->quantity_reversed = $this->math->add((string) $line->quantity_reversed, (string) $issue->quantity_issued);
        $line->save();

        $allocation = InventoryAllocation::query()->lockForUpdate()->findOrFail($issue->allocation_id);
        $allocation->quantity_reversed = $this->math->add((string) $allocation->quantity_reversed, (string) $issue->quantity_issued);
        if ($this->math->isZero((string) $allocation->quantity_remaining)
            && $this->math->compare((string) $allocation->quantity_reversed, (string) $allocation->quantity_issued) === 0) {
            $allocation->status = $this->math->isZero((string) $allocation->quantity_released)
                ? AllocationStatus::Reversed
                : AllocationStatus::Released;
        }
        $allocation->save();
    }

    private function defaultFromState(InventoryDirection $direction): ?InventoryStockState
    {
        return $direction === InventoryDirection::Out ? InventoryStockState::Available : null;
    }

    private function defaultToState(InventoryDirection $direction): InventoryStockState
    {
        return $direction === InventoryDirection::Out
            ? InventoryStockState::Issued
            : InventoryStockState::Available;
    }
}
