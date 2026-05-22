<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use InvalidArgumentException;
use Modules\Inventory\Application\DTOs\ValuationLayerConsumption;
use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\DTOs\ValuationResult;
use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Enums\StockDirection;
use Modules\Inventory\Domain\Enums\ValuationMethod;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;

final class FifoValuationStrategy implements ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult
    {
        $direction = StockDirection::normalize($request->direction);

        if ($direction === StockDirection::IN) {
            $unitCost = (float) ($request->unitCost ?? 0);
            if ($unitCost <= 0) {
                throw new InvalidArgumentException('Unit cost is required for inbound FIFO valuation.');
            }

            return new ValuationResult(
                valuationMethod: ValuationMethod::FIFO,
                direction: $direction,
                quantity: $request->quantity,
                unitCost: $unitCost,
                totalCost: $request->quantity * $unitCost,
            );
        }

        $query = InventoryCostLayerModel::query()
            ->where('tenant_id', $request->tenantId)
            ->where('item_id', $request->itemId)
            ->where('quantity_remaining', '>', 0)
            ->where('is_closed', false)
            ->orderBy('layer_date')
            ->orderBy('id');

        if ($request->variantId !== null) {
            $query->where('variant_id', $request->variantId);
        }

        if ($request->locationId !== null) {
            $query->where('location_id', $request->locationId);
        }

        if ($request->warehouseId !== null) {
            $query->where('warehouse_id', $request->warehouseId);
        }

        $layers = $query->lockForUpdate()->get();

        $remaining = $request->quantity;
        $totalCost = 0.0;
        $consumptions = [];

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $layer->quantity_remaining;
            if ($available <= 0) {
                continue;
            }

            $consumeQty = min($available, $remaining);
            $unitCost = (float) $layer->unit_cost;
            $totalCost += $consumeQty * $unitCost;
            $remaining -= $consumeQty;

            $consumptions[] = new ValuationLayerConsumption(
                layerId: (int) $layer->id,
                consumedQuantity: $consumeQty,
                unitCost: $unitCost,
            );
        }

        if ($remaining > 0) {
            throw new InvalidArgumentException('Insufficient inventory layers for FIFO valuation.');
        }

        $unitCost = $request->quantity > 0 ? $totalCost / $request->quantity : 0.0;

        return new ValuationResult(
            valuationMethod: ValuationMethod::FIFO,
            direction: $direction,
            quantity: $request->quantity,
            unitCost: $unitCost,
            totalCost: $totalCost,
            consumptions: $consumptions,
        );
    }
}
