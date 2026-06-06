<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryAdjustmentLine;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockAdjustmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockMovementService $movements,
    ) {}

    public function create(StockAdjustmentData $data): InventoryAdjustment
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Inventory adjustment requires at least one line.');
        }

        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);

        return DB::transaction(function () use ($data): InventoryAdjustment {
            $adjustment = InventoryAdjustment::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'adjustment_number' => $data->adjustmentNumber ?? $this->numbers->next($data->tenantId, 'ADJ', 'inventory_adjustments', 'adjustment_number'),
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
        if ($adjustment->status !== AdjustmentStatus::Draft) {
            throw new InvalidArgumentException('Only draft inventory adjustments can be approved.');
        }

        $adjustment->status = AdjustmentStatus::Approved;
        $adjustment->approved_by = $approvedBy;
        $adjustment->approved_at = now();
        $adjustment->save();

        return $adjustment->refresh();
    }

    public function post(InventoryAdjustment $adjustment, ?int $postedBy = null): InventoryAdjustment
    {
        if (! in_array($adjustment->status, [AdjustmentStatus::Draft, AdjustmentStatus::Approved], true)) {
            throw new InvalidArgumentException('Only draft or approved inventory adjustments can be posted.');
        }

        return DB::transaction(function () use ($adjustment, $postedBy): InventoryAdjustment {
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

    private function createLine(InventoryAdjustment $adjustment, StockAdjustmentLineData $data): InventoryAdjustmentLine
    {
        $this->validator->assertNonNegative($data->systemQuantity, 'Inventory adjustment system quantity cannot be negative.');
        $this->validator->assertNonNegative($data->countedQuantity, 'Inventory adjustment counted quantity cannot be negative.');
        $this->validator->assertNonNegative($data->unitCost, 'Inventory adjustment unit cost cannot be negative.');
        $item = $this->validator->item((int) $adjustment->tenant_id, $adjustment->organization_unit_id, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $this->validator->batch($item, $data->batchId);
        $this->validator->serial($item, $data->serialNumberId, ltrim($data->adjustmentQuantity, '-'));

        $quantity = $this->math->normalize($data->adjustmentQuantity);

        return InventoryAdjustmentLine::query()->create([
            'tenant_id' => $adjustment->tenant_id,
            'organization_unit_id' => $adjustment->organization_unit_id,
            'inventory_adjustment_id' => $adjustment->getKey(),
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'system_quantity' => $this->math->normalize($data->systemQuantity),
            'counted_quantity' => $this->math->normalize($data->countedQuantity),
            'adjustment_quantity' => $quantity,
            'unit_cost' => $this->math->normalize($data->unitCost),
            'total_cost' => $this->math->mul(ltrim($quantity, '-'), $data->unitCost),
            'reason' => $data->reason,
        ]);
    }
}
