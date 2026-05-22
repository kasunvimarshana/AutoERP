<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\DTOs\ValuationResult;
use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Enums\StockDirection;
use Modules\Inventory\Domain\Enums\ValuationMethod;

final class WeightedAverageValuationStrategy implements ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult
    {
        $direction = StockDirection::normalize($request->direction);
        $snapshot = $this->getCurrentSnapshot($request);
        $currentQty = (float) ($snapshot->qty ?? 0);
        $currentValue = (float) ($snapshot->value ?? 0);
        $currentAvg = $currentQty > 0 ? $currentValue / $currentQty : 0.0;

        if ($direction === StockDirection::IN) {
            $unitCost = (float) ($request->unitCost ?? 0);
            if ($unitCost <= 0) {
                throw new InvalidArgumentException('Unit cost is required for inbound weighted average valuation.');
            }

            $totalCost = $request->quantity * $unitCost;

            return new ValuationResult(
                valuationMethod: ValuationMethod::WEIGHTED_AVERAGE,
                direction: $direction,
                quantity: $request->quantity,
                unitCost: $unitCost,
                totalCost: $totalCost,
            );
        }

        if ($currentQty < $request->quantity) {
            throw new InvalidArgumentException('Insufficient stock for weighted average valuation.');
        }

        $totalCost = $request->quantity * $currentAvg;

        return new ValuationResult(
            valuationMethod: ValuationMethod::WEIGHTED_AVERAGE,
            direction: $direction,
            quantity: $request->quantity,
            unitCost: $currentAvg,
            totalCost: $totalCost,
        );
    }

    private function getCurrentSnapshot(ValuationRequest $request): object
    {
        $query = DB::table('stock_levels as sl')
            ->selectRaw('COALESCE(SUM(sl.quantity_on_hand), 0) as qty')
            ->selectRaw('COALESCE(SUM(sl.quantity_on_hand * COALESCE(sl.unit_cost, 0)), 0) as value')
            ->where('sl.tenant_id', $request->tenantId)
            ->where('sl.item_id', $request->itemId);

        if ($request->variantId !== null) {
            $query->where('sl.variant_id', $request->variantId);
        }

        if ($request->locationId !== null) {
            $query->where('sl.location_id', $request->locationId);
        }

        if ($request->warehouseId !== null) {
            $query->join('warehouse_locations as wl', 'wl.id', '=', 'sl.location_id')
                ->where('wl.warehouse_id', $request->warehouseId);
        }

        return $query->lockForUpdate()->first() ?? (object) ['qty' => 0, 'value' => 0];
    }
}
