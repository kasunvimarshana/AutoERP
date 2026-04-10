<?php

<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;

class AllocationService
{
    protected $rotationService;

    public function __construct(RotationService $rotationService)
    {
        $this->rotationService = $rotationService;
    }

    public function allocate($productId, $warehouseId, $quantity, $strategy = 'nearest_expiry')
    {
        $stocks = $this->rotationService->getPickingOrder($productId, $warehouseId, $this->mapStrategy($strategy));
        $allocated = [];
        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) break;
            $available = $stock->quantity - $stock->reserved_quantity;
            $take = min($remaining, $available);
            if ($take > 0) {
                $stock->reserved_quantity += $take;
                $stock->save();
                $allocated[] = [
                    'stock_id' => $stock->id,
                    'batch_id' => $stock->batch_id,
                    'quantity' => $take,
                    'unit_cost' => $stock->unit_cost,
                ];
                $remaining -= $take;
            }
        }

        if ($remaining > 0) {
            throw new \Exception("Cannot allocate full quantity {$quantity}");
        }

        return $allocated;
    }

    public function release($allocations)
    {
        foreach ($allocations as $alloc) {
            $stock = InventoryStock::find($alloc['stock_id']);
            if ($stock) {
                $stock->reserved_quantity -= $alloc['quantity'];
                $stock->save();
            }
        }
    }

    protected function mapStrategy($strategy)
    {
        return match($strategy) {
            'nearest_expiry' => 'fefo',
            'oldest_stock' => 'fifo',
            default => 'fefo',
        };
    }
}