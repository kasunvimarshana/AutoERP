<?php

class RotationService
{
    public function getPickingOrder($productId, $warehouseId, $strategy = 'fefo')
    {
        $query = InventoryStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0);

        if ($strategy === 'fefo') {
            $query->join('batches', 'inventory_stocks.batch_id', '=', 'batches.id')
                  ->orderBy('batches.expiry_date', 'asc')
                  ->select('inventory_stocks.*');
        } elseif ($strategy === 'fifo') {
            $query->orderBy('created_at', 'asc');
        } elseif ($strategy === 'lifo') {
            $query->orderBy('created_at', 'desc');
        }

        return $query->get();
    }
}