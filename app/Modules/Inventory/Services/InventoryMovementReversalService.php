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
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Item\Enums\ItemBaseUomRevisionStatus;
use Modules\Item\Models\ItemBaseUomRevision;

final class InventoryMovementReversalService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryMovementRecorder $recorder,
        private readonly InventoryMovementPoster $poster,
    ) {}

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
            $reversal = $this->recorder->create(new StockMovementData(
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
            $reversal = $this->poster->post($reversal, $reversedBy);

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
}
