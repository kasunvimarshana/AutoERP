<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\CostAdjustmentData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\CostAdjustmentStatus;
use Modules\Inventory\Enums\ValuationLayerStatus;
use Modules\Inventory\Models\InventoryCostAdjustment;
use Modules\Inventory\Models\InventoryCostAdjustmentLine;
use Modules\Inventory\Models\InventoryValuationLayer;

final class InventoryCostAdjustmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
    ) {}

    public function create(CostAdjustmentData $data): InventoryCostAdjustment
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Inventory cost adjustment requires at least one line.');
        }

        return DB::transaction(function () use ($data): InventoryCostAdjustment {
            $adjustment = InventoryCostAdjustment::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'adjustment_number' => $data->adjustmentNumber ?? $this->numbers->next($data->tenantId, 'CADJ', 'inventory_cost_adjustments', 'adjustment_number'),
                'adjustment_date' => $data->adjustmentDate,
                'status' => CostAdjustmentStatus::Draft,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $amount = $this->math->normalize($line->adjustmentAmount);
                if ($this->math->isZero($amount)) {
                    throw new InvalidArgumentException('Inventory cost adjustment amount cannot be zero.');
                }

                $layer = InventoryValuationLayer::query()->findOrFail($line->valuationLayerId);
                if ((int) $layer->tenant_id !== $data->tenantId
                    || $layer->organization_unit_id !== $data->organizationUnitId) {
                    throw new InvalidArgumentException('Inventory valuation layer belongs to a different scope.');
                }

                InventoryCostAdjustmentLine::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'inventory_cost_adjustment_id' => $adjustment->getKey(),
                    'valuation_layer_id' => $layer->getKey(),
                    'adjustment_amount' => $amount,
                    'remaining_quantity' => $layer->remaining_quantity,
                    'unit_cost_before' => $layer->unit_cost,
                    'unit_cost_after' => $layer->unit_cost,
                    'remaining_value_before' => $layer->remaining_value,
                    'remaining_value_after' => $layer->remaining_value,
                    'reason' => $line->reason,
                ]);
            }

            return $adjustment->refresh()->load('lines.valuationLayer');
        });
    }

    public function post(InventoryCostAdjustment $adjustment, ?int $postedBy = null): InventoryCostAdjustment
    {
        return DB::transaction(function () use ($adjustment, $postedBy): InventoryCostAdjustment {
            $adjustment = InventoryCostAdjustment::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($adjustment->getKey());
            if ($adjustment->status !== CostAdjustmentStatus::Draft) {
                throw new InvalidArgumentException('Only draft inventory cost adjustments can be posted.');
            }

            foreach ($adjustment->lines as $line) {
                $layer = InventoryValuationLayer::query()->lockForUpdate()->findOrFail($line->valuation_layer_id);
                if ($layer->status === ValuationLayerStatus::Closed || $this->math->isZero((string) $layer->remaining_quantity)) {
                    throw new InvalidArgumentException('Fully consumed valuation layers cannot be cost adjusted.');
                }

                $newRemainingValue = $this->math->add((string) $layer->remaining_value, (string) $line->adjustment_amount);
                if ($this->math->isNegative($newRemainingValue)) {
                    throw new InvalidArgumentException('Inventory cost adjustment cannot make valuation layer value negative.');
                }

                $line->remaining_quantity = $layer->remaining_quantity;
                $line->unit_cost_before = $layer->unit_cost;
                $line->remaining_value_before = $layer->remaining_value;

                $layer->remaining_value = $newRemainingValue;
                $layer->total_cost = $this->math->add((string) $layer->total_cost, (string) $line->adjustment_amount);
                $layer->unit_cost = $this->math->div($newRemainingValue, (string) $layer->remaining_quantity);
                $layer->save();

                $line->unit_cost_after = $layer->unit_cost;
                $line->remaining_value_after = $layer->remaining_value;
                $line->save();

                $balance = $this->balances->getOrCreateForUpdate(new StockBalanceData(
                    tenantId: (int) $layer->tenant_id,
                    itemId: (int) $layer->item_id,
                    warehouseId: (int) $layer->warehouse_id,
                    organizationUnitId: $layer->organization_unit_id,
                    itemVariantId: $layer->item_variant_id,
                    warehouseLocationId: $layer->warehouse_location_id,
                    batchId: $layer->batch_id,
                ));
                $newBalanceValue = $this->math->add((string) $balance->total_value, (string) $line->adjustment_amount);
                if ($this->math->isNegative($newBalanceValue)) {
                    throw new InvalidArgumentException('Inventory cost adjustment cannot make stock balance value negative.');
                }
                $balance->total_value = $newBalanceValue;
                $balance->average_cost = $this->math->isZero((string) $balance->quantity_on_hand)
                    ? '0.000000'
                    : $this->math->div($newBalanceValue, (string) $balance->quantity_on_hand);
                $balance->save();
            }

            $adjustment->status = CostAdjustmentStatus::Posted;
            $adjustment->posted_by = $postedBy;
            $adjustment->posted_at = now();
            $adjustment->save();

            return $adjustment->refresh()->load('lines.valuationLayer');
        });
    }
}
