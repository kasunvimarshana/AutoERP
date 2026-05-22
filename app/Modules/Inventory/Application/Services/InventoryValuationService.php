<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\DTOs\ValuationResult;
use Modules\Inventory\Application\Factories\ValuationStrategyFactory;
use Modules\Inventory\Domain\Enums\StockDirection;
use Modules\Inventory\Domain\Enums\ValuationMethod;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class InventoryValuationService
{
    public function __construct(
        private readonly ValuationStrategyFactory $strategyFactory,
    ) {
    }

    public function process(ValuationRequest $request): ValuationResult
    {
        return DB::transaction(function () use ($request): ValuationResult {
            $method = $this->resolveValuationMethod($request);
            $strategy = $this->strategyFactory->make($method);
            $result = $strategy->calculate($request);

            $this->applyCostLayerChanges($request, $result);
            [$balanceQty, $balanceValue] = $this->updateStockLevelAndGetBalance($request, $result, $method);

            $result->balanceQuantity = $balanceQty;
            $result->balanceValue = $balanceValue;

            $this->recordStockMovement($request, $result, $method);

            return $result;
        });
    }

    private function resolveValuationMethod(ValuationRequest $request): string
    {
        if ($request->valuationMethod !== null && $request->valuationMethod !== '') {
            return ValuationMethod::normalize($request->valuationMethod);
        }

        $config = ValuationConfigModel::query()
            ->where('tenant_id', $request->tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($request): void {
                $query->where('item_id', $request->itemId)->orWhereNull('item_id');
            })
            ->where(function ($query) use ($request): void {
                $query->where('warehouse_id', $request->warehouseId)->orWhereNull('warehouse_id');
            })
            ->where(function ($query) use ($request): void {
                $query->where('transaction_type', $request->txnType)->orWhereNull('transaction_type');
            })
            ->orderByRaw('CASE WHEN item_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN warehouse_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN transaction_type IS NULL THEN 1 ELSE 0 END')
            ->first();

        if ($config !== null && !empty($config->valuation_method)) {
            return ValuationMethod::normalize((string) $config->valuation_method);
        }

        $item = ItemModel::query()->find($request->itemId);

        return ValuationMethod::normalize(
            $item?->valuation_method,
            (string) config('inventory.valuation.default_method', ValuationMethod::FIFO)
        );
    }

    private function applyCostLayerChanges(ValuationRequest $request, ValuationResult $result): void
    {
        $method = ValuationMethod::normalize($result->valuationMethod);
        $direction = StockDirection::normalize($request->direction);

        if (
            $direction === StockDirection::IN
            && in_array($method, [ValuationMethod::FIFO, ValuationMethod::LIFO], true)
        ) {
            InventoryCostLayerModel::query()->create([
                'tenant_id' => $request->tenantId,
                'organization_unit_id' => $request->organizationUnitId,
                'metadata' => !empty($request->metadata) ? $request->metadata : null,
                'item_id' => $request->itemId,
                'variant_id' => $request->variantId,
                'batch_id' => $request->batchId,
                'serial_id' => $request->serialId,
                'warehouse_id' => $request->warehouseId,
                'location_id' => $request->locationId,
                'valuation_method' => $method,
                'layer_date' => ($request->layerDate ?? now())->format('Y-m-d'),
                'quantity_in' => $request->quantity,
                'quantity_remaining' => $request->quantity,
                'unit_cost' => $result->unitCost,
                'reference_type' => $request->referenceType,
                'reference_id' => $request->referenceId,
                'is_closed' => false,
            ]);

            return;
        }

        if (
            $direction === StockDirection::OUT
            && in_array($method, [ValuationMethod::FIFO, ValuationMethod::LIFO], true)
        ) {
            foreach ($result->consumptions as $consumption) {
                $layer = InventoryCostLayerModel::query()->lockForUpdate()->find($consumption->layerId);
                if ($layer === null) {
                    continue;
                }

                $remaining = max(0.0, (float) $layer->quantity_remaining - $consumption->consumedQuantity);
                $layer->quantity_remaining = $remaining;
                $layer->is_closed = $remaining <= 0;
                $layer->row_version = ((int) $layer->row_version) + 1;
                $layer->save();
            }
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function updateStockLevelAndGetBalance(
        ValuationRequest $request,
        ValuationResult $result,
        string $method
    ): array {
        $direction = StockDirection::normalize($request->direction);

        $level = StockLevelModel::query()
            ->where('tenant_id', $request->tenantId)
            ->where('item_id', $request->itemId)
            ->where('location_id', $request->locationId)
            ->where('variant_id', $request->variantId)
            ->where('batch_id', $request->batchId)
            ->where('serial_id', $request->serialId)
            ->where('condition', 'good')
            ->lockForUpdate()
            ->first();

        if ($level === null) {
            $level = new StockLevelModel();
            $level->tenant_id = $request->tenantId;
            $level->organization_unit_id = $request->organizationUnitId;
            $level->metadata = !empty($request->metadata) ? $request->metadata : null;
            $level->item_id = $request->itemId;
            $level->variant_id = $request->variantId;
            $level->location_id = $request->locationId;
            $level->batch_id = $request->batchId;
            $level->serial_id = $request->serialId;
            $level->uom_id = $request->uomId;
            $level->quantity_on_hand = 0;
            $level->quantity_reserved = 0;
            $level->unit_cost = null;
            $level->condition = 'good';
            $level->row_version = 1;
        }

        $currentQty = (float) $level->quantity_on_hand;
        $currentCost = (float) ($level->unit_cost ?? 0);

        if ($direction === StockDirection::IN) {
            $newQty = $currentQty + $request->quantity;

            if (ValuationMethod::normalize($method) === ValuationMethod::WEIGHTED_AVERAGE) {
                $newValue = ($currentQty * $currentCost) + $result->totalCost;
                $level->unit_cost = $newQty > 0 ? $newValue / $newQty : 0;
            } else {
                $level->unit_cost = $result->unitCost;
            }

            $level->quantity_on_hand = $newQty;
        } else {
            if ($currentQty < $request->quantity) {
                throw new InvalidArgumentException('Insufficient stock level for outbound movement.');
            }

            $level->quantity_on_hand = $currentQty - $request->quantity;
        }

        $level->last_movement_at = $request->performedAt ?? now();
        $level->row_version = ((int) ($level->row_version ?? 0)) + 1;
        $level->save();

        $snapshot = DB::table('stock_levels')
            ->selectRaw('COALESCE(SUM(quantity_on_hand), 0) as qty')
            ->selectRaw('COALESCE(SUM(quantity_on_hand * COALESCE(unit_cost, 0)), 0) as value')
            ->where('tenant_id', $request->tenantId)
            ->where('item_id', $request->itemId)
            ->where('location_id', $request->locationId)
            ->when($request->variantId !== null, static fn ($q) => $q->where('variant_id', $request->variantId))
            ->first();

        return [(float) ($snapshot->qty ?? 0), (float) ($snapshot->value ?? 0)];
    }

    private function recordStockMovement(ValuationRequest $request, ValuationResult $result, string $method): void
    {
        $direction = StockDirection::normalize($request->direction);

        StockMovementModel::query()->create([
            'tenant_id' => $request->tenantId,
            'organization_unit_id' => $request->organizationUnitId,
            'metadata' => !empty($request->metadata) ? $request->metadata : null,
            'direction' => strtolower($direction),
            'item_id' => $request->itemId,
            'variant_id' => $request->variantId,
            'batch_id' => $request->batchId,
            'serial_id' => $request->serialId,
            'location_id' => $request->locationId,
            'warehouse_id' => $request->warehouseId,
            'txn_type' => $request->txnType ?? 'VALUATION',
            'reference_type' => $request->referenceType,
            'reference_id' => $request->referenceId,
            'uom_id' => $request->uomId,
            'quantity' => $request->quantity,
            'quantity_in' => $direction === StockDirection::IN ? $request->quantity : 0,
            'quantity_out' => $direction === StockDirection::OUT ? $request->quantity : 0,
            'unit_cost' => $result->unitCost,
            'total_cost' => $result->totalCost,
            'balance_quantity' => $result->balanceQuantity,
            'balance_value' => $result->balanceValue,
            'performed_by' => $request->performedBy,
            'performed_at' => $request->performedAt ?? now(),
            'notes' => $request->notes ?? ('Valuation method: ' . $method),
        ]);
    }
}
