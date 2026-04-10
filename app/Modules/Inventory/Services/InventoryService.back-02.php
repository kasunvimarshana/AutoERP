<?php

class InventoryService
{
    public function receiveStock($productId, $warehouseId, $quantity, $unitCost, $batchNumber = null, $serialNumbers = [], $locationId = null, $uomId = null)
    {
        // Convert UOM, create batch/lot, add valuation layer, update stock, record transaction
    }

    public function issueStock($productId, $warehouseId, $quantity, $allocationStrategy = 'nearest_expiry')
    {
        // Allocate, consume layers, update stock, record transaction
    }

    public function adjustStock($productId, $warehouseId, $quantityChange, $reason, $referenceId = null, $locationId = null, $batchId = null, $serialId = null, $notes = null, $userId = null)
{
    DB::transaction(function () use ($productId, $warehouseId, $quantityChange, $reason, $referenceId, $locationId, $batchId, $serialId, $notes, $userId) {
        $stock = InventoryStock::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'batch_id' => $batchId,
            'serial_id' => $serialId,
        ]);

        $oldQty = $stock->quantity;
        $stock->quantity += $quantityChange;
        $stock->save();

        // Create transaction
        InventoryTransaction::create([
            'transaction_id' => (string) \Str::uuid(),
            'type' => 'adjustment',
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'batch_id' => $batchId,
            'serial_number' => $serialId ? SerialNumber::find($serialId)->serial : null,
            'quantity' => abs($quantityChange),
            'unit_cost' => $stock->unit_cost ?? 0,
            'total_cost' => abs($quantityChange) * ($stock->unit_cost ?? 0),
            'direction' => $quantityChange > 0 ? 'in' : 'out',
            'reference_type' => 'cycle_count',
            'reference_id' => $referenceId,
            'created_by' => $userId,
            'metadata' => ['reason' => $reason, 'notes' => $notes],
        ]);

        // If valuation layers need adjustment (e.g., for FIFO/LIFO), call valuationService->addLayer or consume
        // For simplicity, we treat adjustments as new layers if positive, or consumption if negative.
        if ($quantityChange > 0) {
            $this->valuationService->setContext($productId, $warehouseId, $this->getValuationMethod($productId, $warehouseId))
                ->addLayer($batchId, $quantityChange, $stock->unit_cost, now(), null, 'adjustment', $referenceId);
        } else {
            $this->valuationService->setContext($productId, $warehouseId, $this->getValuationMethod($productId, $warehouseId))
                ->consume(abs($quantityChange), 'adjustment', $referenceId);
        }
    });
}
}