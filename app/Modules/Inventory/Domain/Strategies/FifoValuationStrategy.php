<?php

namespace Modules\Inventory\Domain\Strategies;

use Modules\Inventory\Domain\Contracts\ValuationStrategy;
use Modules\Inventory\Domain\Repositories\InventoryRepository;
use Modules\Inventory\Domain\ValueObjects\ValuationResult;

class FifoValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];
        $quantityOut = $context['quantity_out'] ?? 0;

        $layers = $this->repo->getLayers($itemId, $warehouseId);

        $remaining = $quantityOut;
        $totalCost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) {
                break;
            }

            $available = $layer->remaining_quantity;

            if ($available <= 0) {
                continue;
            }

            $consume = min($available, $remaining);

            $cost = $consume * $layer->unit_cost;

            $totalCost += $cost;

            // update layer
            $layer->remaining_quantity -= $consume;

            $this->repo->updateLayer($layer->id, [
                'remaining_quantity' => $layer->remaining_quantity
            ]);

            $remaining -= $consume;
        }

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        return new ValuationResult(
            quantity: $quantityOut,
            unitCost: $quantityOut > 0 ? $totalCost / $quantityOut : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}