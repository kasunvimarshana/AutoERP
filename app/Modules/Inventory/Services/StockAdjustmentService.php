<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryAdjustmentLine;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockAdjustmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockMovementService $movements,
        private readonly InventoryUomService $uoms,
        private readonly StockBalanceService $balances,
    ) {}

    public function create(StockAdjustmentData $data): InventoryAdjustment
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Inventory adjustment requires at least one line.');
        }
        $this->assertUniqueLines($data->lines);

        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);

        return DB::transaction(function () use ($data): InventoryAdjustment {
            $adjustment = InventoryAdjustment::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'adjustment_number' => $data->adjustmentNumber ?? $this->numbers->next($data->tenantId, 'ADJ'),
                'adjustment_date' => $data->adjustmentDate,
                'adjustment_type' => $data->adjustmentType,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'status' => AdjustmentStatus::Draft,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $this->createLine($adjustment, $line);
            }

            return $adjustment->refresh()->load('lines');
        });
    }

    public function approve(InventoryAdjustment $adjustment, ?int $approvedBy = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $approvedBy): InventoryAdjustment {
            $adjustment = InventoryAdjustment::query()->lockForUpdate()->findOrFail($adjustment->getKey());
            if ($adjustment->status !== AdjustmentStatus::Draft) {
                throw new InvalidArgumentException('Only draft inventory adjustments can be approved.');
            }

            $adjustment->status = AdjustmentStatus::Approved;
            $adjustment->approved_by = $approvedBy;
            $adjustment->approved_at = now();
            $adjustment->save();

            return $adjustment->refresh();
        });
    }

    public function post(InventoryAdjustment $adjustment, ?int $postedBy = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $postedBy): InventoryAdjustment {
            $adjustment = InventoryAdjustment::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($adjustment->getKey());
            if (! in_array($adjustment->status, [AdjustmentStatus::Draft, AdjustmentStatus::Approved], true)) {
                throw new InvalidArgumentException('Only draft or approved inventory adjustments can be posted.');
            }

            $systemQuantities = [];
            foreach ($adjustment->lines as $line) {
                if ($this->math->isZero((string) $line->adjustment_quantity)) {
                    continue;
                }

                $dimensionKey = implode(':', [
                    $line->item_id,
                    $line->item_variant_id ?? 'null',
                    $adjustment->warehouse_id,
                    $adjustment->warehouse_location_id ?? 'null',
                    $line->batch_id ?? 'null',
                ]);
                if (isset($systemQuantities[$dimensionKey])) {
                    if ($this->math->compare(
                        $systemQuantities[$dimensionKey],
                        (string) $line->system_quantity,
                    ) !== 0) {
                        throw new InvalidArgumentException(
                            'Inventory adjustment lines for the same stock balance must use one system quantity.',
                        );
                    }

                    continue;
                }

                $balance = $this->balances->getOrCreateForUpdate(new StockBalanceData(
                    tenantId: (int) $adjustment->tenant_id,
                    itemId: (int) $line->item_id,
                    warehouseId: (int) $adjustment->warehouse_id,
                    organizationUnitId: $adjustment->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $adjustment->warehouse_location_id,
                    batchId: $line->batch_id,
                ));
                if ($this->math->compare(
                    (string) $balance->quantity_on_hand,
                    (string) $line->system_quantity,
                ) !== 0) {
                    throw new InvalidArgumentException(
                        'Inventory stock changed after the adjustment was created. Create a new adjustment before posting.',
                    );
                }
                $systemQuantities[$dimensionKey] = (string) $line->system_quantity;
            }

            foreach ($adjustment->lines as $line) {
                if ($this->math->isZero((string) $line->adjustment_quantity)) {
                    continue;
                }
                $direction = $this->math->isNegative((string) $line->adjustment_quantity) ? InventoryDirection::Out : InventoryDirection::In;
                $quantity = ltrim((string) $line->adjustment_quantity, '-');

                $this->movements->record(new StockMovementData(
                    tenantId: (int) $adjustment->tenant_id,
                    movementDate: $adjustment->adjustment_date->toDateString(),
                    movementType: $direction === InventoryDirection::In ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut,
                    direction: $direction,
                    itemId: (int) $line->item_id,
                    warehouseId: (int) $adjustment->warehouse_id,
                    quantity: $quantity,
                    organizationUnitId: $adjustment->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $adjustment->warehouse_location_id,
                    batchId: $line->batch_id,
                    serialNumberId: $line->serial_number_id,
                    unitCost: (string) $line->unit_cost,
                    sourceType: 'inventory_adjustment',
                    sourceId: (int) $adjustment->getKey(),
                    sourceLineType: 'inventory_adjustment_line',
                    sourceLineId: (int) $line->getKey(),
                    description: $line->reason,
                ), $postedBy);
            }

            $adjustment->status = AdjustmentStatus::Posted;
            $adjustment->posted_by = $postedBy;
            $adjustment->posted_at = now();
            $adjustment->save();

            return $adjustment->refresh();
        });
    }

    public function reverse(InventoryAdjustment $adjustment, ?int $reversedBy = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $reversedBy): InventoryAdjustment {
            $adjustment = InventoryAdjustment::query()->lockForUpdate()->findOrFail($adjustment->getKey());
            if ($adjustment->status !== AdjustmentStatus::Posted) {
                throw new InvalidArgumentException('Only posted inventory adjustments can be reversed.');
            }

            $movements = InventoryMovement::query()
                ->where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->getKey())
                ->where('status', InventoryStatus::Posted->value)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            foreach ($movements as $movement) {
                $this->movements->reverse($movement, $reversedBy);
            }

            $adjustment->status = AdjustmentStatus::Reversed;
            $adjustment->save();

            return $adjustment->refresh();
        });
    }

    private function createLine(InventoryAdjustment $adjustment, StockAdjustmentLineData $data): InventoryAdjustmentLine
    {
        $enteredSystemQuantity = $this->math->normalize($data->systemQuantity);
        $enteredCountedQuantity = $this->math->normalize($data->countedQuantity);
        $enteredAdjustmentQuantity = $this->math->normalize($data->adjustmentQuantity);
        $enteredUnitCost = $this->math->normalize($data->unitCost);
        $this->validator->assertNonNegative($enteredSystemQuantity, 'Inventory adjustment system quantity cannot be negative.');
        $this->validator->assertNonNegative($enteredCountedQuantity, 'Inventory adjustment counted quantity cannot be negative.');
        $this->validator->assertNonNegative($enteredUnitCost, 'Inventory adjustment unit cost cannot be negative.');
        if ($this->math->compare(
            $enteredAdjustmentQuantity,
            $this->math->sub($enteredCountedQuantity, $enteredSystemQuantity),
        ) !== 0) {
            throw new InvalidArgumentException(
                'Inventory adjustment quantity must equal counted quantity minus system quantity.',
            );
        }
        $item = $this->validator->item((int) $adjustment->tenant_id, $adjustment->organization_unit_id, $data->itemId);
        $basis = $this->uoms->basis(
            (int) $adjustment->tenant_id,
            $adjustment->organization_unit_id,
            $item,
            $data->uomId,
            $enteredAdjustmentQuantity,
            $enteredUnitCost,
        );
        $systemQuantity = $this->math->mul($enteredSystemQuantity, $basis->conversionFactor);
        $countedQuantity = $this->math->mul($enteredCountedQuantity, $basis->conversionFactor);
        $quantity = $basis->baseQuantity;
        $unitCost = $basis->baseUnitCost;
        $item = $item->refresh();
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        $this->validator->serial(
            $item,
            $data->serialNumberId,
            ltrim($quantity, '-'),
            $data->itemVariantId,
            $data->batchId,
        );

        return InventoryAdjustmentLine::query()->create([
            'tenant_id' => $adjustment->tenant_id,
            'organization_unit_id' => $adjustment->organization_unit_id,
            'inventory_adjustment_id' => $adjustment->getKey(),
            'item_id' => $data->itemId,
            'base_uom_id' => $basis->baseUomId,
            'entered_uom_id' => $basis->enteredUomId,
            'item_variant_id' => $data->itemVariantId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'entered_system_quantity' => $enteredSystemQuantity,
            'entered_counted_quantity' => $enteredCountedQuantity,
            'entered_adjustment_quantity' => $enteredAdjustmentQuantity,
            'entered_unit_cost' => $enteredUnitCost,
            'conversion_factor' => $basis->conversionFactor,
            'system_quantity' => $systemQuantity,
            'counted_quantity' => $countedQuantity,
            'adjustment_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $this->math->mul(ltrim($quantity, '-'), $unitCost),
            'reason' => $data->reason,
        ]);
    }

    /**
     * @param  list<StockAdjustmentLineData>  $lines
     */
    private function assertUniqueLines(array $lines): void
    {
        $seen = [];
        foreach ($lines as $line) {
            $key = implode(':', [
                $line->itemId,
                $line->itemVariantId ?? 'null',
                $line->batchId ?? 'null',
                $line->serialNumberId ?? 'null',
            ]);
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Inventory adjustment contains duplicate stock dimension lines.');
            }
            $seen[$key] = true;
        }
    }
}
