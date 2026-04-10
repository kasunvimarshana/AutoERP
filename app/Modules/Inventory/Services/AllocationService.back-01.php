<?php

namespace App\Services\Inventory;

class AllocationService
{
    public function allocate($productId, $warehouseId, $quantity, $strategy = 'nearest_expiry')
    {
        $stocks = $this->getAvailableStock($productId, $warehouseId, $strategy);
        $allocated = [];
        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) break;
            $take = min($remaining, $stock->quantity - $stock->reserved_quantity);
            if ($take > 0) {
                $stock->reserved_quantity += $take;
                $stock->save();
                $allocated[] = [
                    'stock_id' => $stock->id,
                    'batch_id' => $stock->batch_id,
                    'quantity' => $take,
                ];
                $remaining -= $take;
            }
        }

        if ($remaining > 0) {
            throw new \Exception("Cannot allocate full quantity");
        }

        return $allocated;
    }

    protected function getAvailableStock($productId, $warehouseId, $strategy)
    {
        $query = InventoryStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereRaw('quantity > reserved_quantity');

        if ($strategy === 'nearest_expiry') {
            return $query->join('batches', 'inventory_stocks.batch_id', '=', 'batches.id')
                ->orderBy('batches.expiry_date', 'asc')
                ->select('inventory_stocks.*')
                ->get();
        }
        return $query->orderBy('created_at', 'asc')->get(); // FIFO
    }
}