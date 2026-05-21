<?php

namespace Modules\Inventory\Domain\Strategies;

use Modules\Inventory\Domain\Contracts\ValuationStrategy;
use Modules\Inventory\Domain\Repositories\InventoryRepository;
use Modules\Inventory\Domain\ValueObjects\ValuationResult;

class AverageValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];

        $inQty = $context['quantity_in'] ?? 0;
        $outQty = $context['quantity_out'] ?? 0;
        $unitCost = $context['unit_cost'] ?? 0;

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        $currentQty = $balance->quantity;
        $currentValue = $balance->value;

        // ➜ STOCK IN
        if ($inQty > 0) {

            $newQty = $currentQty + $inQty;
            $newValue = $currentValue + ($inQty * $unitCost);

            $avgCost = $newQty > 0 ? $newValue / $newQty : 0;

            $this->repo->updateBalance($itemId, $warehouseId, [
                'quantity' => $newQty,
                'value' => $newValue,
                'average_cost' => $avgCost,
            ]);

            return new ValuationResult(
                quantity: $inQty,
                unitCost: $avgCost,
                totalCost: $inQty * $avgCost,
                balanceQuantity: $newQty,
                balanceValue: $newValue
            );
        }

        // ➜ STOCK OUT
        $avgCost = $balance->average_cost;

        $newQty = $currentQty - $outQty;
        $newValue = $currentValue - ($outQty * $avgCost);

        $this->repo->updateBalance($itemId, $warehouseId, [
            'quantity' => $newQty,
            'value' => $newValue,
        ]);

        return new ValuationResult(
            quantity: $outQty,
            unitCost: $avgCost,
            totalCost: $outQty * $avgCost,
            balanceQuantity: $newQty,
            balanceValue: $newValue
        );
    }
}
