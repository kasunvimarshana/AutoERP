<?php

namespace Modules\Inventory\Domain\Strategies;

use Modules\Inventory\Domain\Contracts\ValuationStrategy;
use Modules\Inventory\Domain\Repositories\InventoryRepository;
use Modules\Inventory\Domain\ValueObjects\ValuationResult;

class SpecificValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];
        $serials = $context['serials'] ?? [];

        $totalCost = 0;
        $qty = count($serials);

        foreach ($serials as $serial) {

            $record = $this->repo->getSerial($serial);

            if (!$record || $record->status !== 'AVAILABLE') {
                throw new \Exception("Invalid serial: {$serial}");
            }

            $totalCost += $record->cost;

            $this->repo->updateSerial($serial, [
                'status' => 'SOLD'
            ]);
        }

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        return new ValuationResult(
            quantity: $qty,
            unitCost: $qty > 0 ? $totalCost / $qty : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
