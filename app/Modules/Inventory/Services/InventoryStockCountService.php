<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Models\InventoryAdjustmentLine;
use Modules\Inventory\Models\InventoryStockCount;
use Modules\Inventory\Models\InventoryStockCountLine;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryStockCountService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly InventoryAvailabilityService $availability,
        private readonly StockBalanceService $balances,
        private readonly StockAdjustmentService $adjustments,
        private readonly InventoryUomService $uoms,
    ) {}

    public function create(StockCountData $data): InventoryStockCount
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Inventory stock count requires at least one line.');
        }

        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);

        return DB::transaction(function () use ($data): InventoryStockCount {
            $count = InventoryStockCount::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'count_number' => $data->countNumber ?? $this->numbers->next($data->tenantId, 'CNT', 'inventory_stock_counts', 'count_number'),
                'count_date' => $data->countDate,
                'count_type' => $data->countType,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'status' => StockCountStatus::Draft,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
                $countedQuantity = $this->math->normalize($line->countedQuantity);
                $systemQuantity = $line->systemQuantity === null
                    ? null
                    : $this->math->normalize($line->systemQuantity);
                $unitCost = $line->unitCost === null ? null : $this->math->normalize($line->unitCost);

                if ($line->uomId !== null) {
                    $countedQuantity = $this->uoms->quantity($data->tenantId, $data->organizationUnitId, $item, $line->uomId, $countedQuantity);
                    if ($systemQuantity !== null) {
                        $systemQuantity = $this->uoms->quantity($data->tenantId, $data->organizationUnitId, $item, $line->uomId, $systemQuantity);
                    }
                    if ($unitCost !== null) {
                        $unitCost = $this->uoms->basis($data->tenantId, $data->organizationUnitId, $item, $line->uomId, '1.000000', $unitCost)['unit_cost'];
                    }
                    $item = $item->refresh();
                }

                $this->validator->assertStockable($item);
                $this->validator->variant($item, $line->itemVariantId);
                $this->validator->batch($item, $line->batchId);
                $this->validator->serial($item, $line->serialNumberId, $countedQuantity);
                $this->validator->assertNonNegative($countedQuantity, 'Inventory counted quantity cannot be negative.');

                $balance = $this->balances->getOrCreate(new StockBalanceData(
                    tenantId: $data->tenantId,
                    itemId: $line->itemId,
                    warehouseId: $data->warehouseId,
                    organizationUnitId: $data->organizationUnitId,
                    itemVariantId: $line->itemVariantId,
                    warehouseLocationId: $data->warehouseLocationId,
                    batchId: $line->batchId,
                ));
                $systemQuantity ??= (string) $this->availability->availability(new StockBalanceData(
                    tenantId: $data->tenantId,
                    itemId: $line->itemId,
                    warehouseId: $data->warehouseId,
                    organizationUnitId: $data->organizationUnitId,
                    itemVariantId: $line->itemVariantId,
                    warehouseLocationId: $data->warehouseLocationId,
                    batchId: $line->batchId,
                ))->quantityOnHand;
                $unitCost ??= (string) $balance->average_cost;

                InventoryStockCountLine::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'inventory_stock_count_id' => $count->getKey(),
                    'item_id' => $line->itemId,
                    'item_variant_id' => $line->itemVariantId,
                    'batch_id' => $line->batchId,
                    'serial_number_id' => $line->serialNumberId,
                    'system_quantity' => $systemQuantity,
                    'counted_quantity' => $countedQuantity,
                    'variance_quantity' => $this->math->sub($countedQuantity, $systemQuantity),
                    'unit_cost' => $unitCost,
                    'notes' => $line->notes,
                ]);
            }

            return $count->refresh()->load('lines');
        });
    }

    public function approve(InventoryStockCount $count, ?int $approvedBy = null): InventoryStockCount
    {
        return DB::transaction(function () use ($count, $approvedBy): InventoryStockCount {
            $count = InventoryStockCount::query()->lockForUpdate()->findOrFail($count->getKey());
            if ($count->status !== StockCountStatus::Draft) {
                throw new InvalidArgumentException('Only draft inventory stock counts can be approved.');
            }

            $count->status = StockCountStatus::Approved;
            $count->approved_by = $approvedBy;
            $count->approved_at = now();
            $count->save();

            return $count->refresh();
        });
    }

    public function post(InventoryStockCount $count, ?int $postedBy = null): InventoryStockCount
    {
        return DB::transaction(function () use ($count, $postedBy): InventoryStockCount {
            $count = InventoryStockCount::query()->with('lines')->lockForUpdate()->findOrFail($count->getKey());
            if (! in_array($count->status, [StockCountStatus::Draft, StockCountStatus::Approved], true)) {
                throw new InvalidArgumentException('Only draft or approved inventory stock counts can be posted.');
            }

            $varianceLines = $count->lines
                ->filter(fn (InventoryStockCountLine $line): bool => ! $this->math->isZero((string) $line->variance_quantity))
                ->values();
            if ($varianceLines->isEmpty()) {
                $count->status = StockCountStatus::Posted;
                $count->posted_by = $postedBy;
                $count->posted_at = now();
                $count->save();

                return $count->refresh()->load('lines');
            }

            $adjustment = $this->adjustments->create(new StockAdjustmentData(
                tenantId: (int) $count->tenant_id,
                adjustmentDate: $count->count_date->toDateString(),
                adjustmentType: AdjustmentType::Recount,
                warehouseId: (int) $count->warehouse_id,
                organizationUnitId: $count->organization_unit_id,
                warehouseLocationId: $count->warehouse_location_id,
                reason: $count->reason ?? 'Stock count '.$count->count_number,
                notes: $count->notes,
                createdBy: $postedBy,
                lines: $varianceLines
                    ->map(fn (InventoryStockCountLine $line): StockAdjustmentLineData => new StockAdjustmentLineData(
                        itemId: (int) $line->item_id,
                        systemQuantity: (string) $line->system_quantity,
                        countedQuantity: (string) $line->counted_quantity,
                        adjustmentQuantity: (string) $line->variance_quantity,
                        unitCost: (string) $line->unit_cost,
                        itemVariantId: $line->item_variant_id,
                        batchId: $line->batch_id,
                        serialNumberId: $line->serial_number_id,
                        reason: 'Stock count '.$count->count_number,
                    ))
                    ->values()
                    ->all(),
            ));
            $this->adjustments->post($adjustment, $postedBy);

            foreach ($count->lines as $line) {
                if ($this->math->isZero((string) $line->variance_quantity)) {
                    continue;
                }
                $adjustmentLine = InventoryAdjustmentLine::query()
                    ->where('inventory_adjustment_id', $adjustment->getKey())
                    ->where('item_id', $line->item_id)
                    ->where('item_variant_id', $line->item_variant_id)
                    ->where('batch_id', $line->batch_id)
                    ->where('serial_number_id', $line->serial_number_id)
                    ->orderByDesc('id')
                    ->first();
                $line->inventory_adjustment_line_id = $adjustmentLine?->getKey();
                $line->save();
            }

            $count->inventory_adjustment_id = $adjustment->getKey();
            $count->status = StockCountStatus::Posted;
            $count->posted_by = $postedBy;
            $count->posted_at = now();
            $count->save();

            return $count->refresh()->load(['lines', 'adjustment.lines']);
        });
    }
}
