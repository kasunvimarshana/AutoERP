<?php

<?php

namespace App\Services\Inventory;

use App\Models\InventoryLayer;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class ValuationService
{
    protected $productId;
    protected $warehouseId;
    protected $method;

    public function setContext($productId, $warehouseId, $method)
    {
        $this->productId = $productId;
        $this->warehouseId = $warehouseId;
        $this->method = $method;
        return $this;
    }

    public function consume($quantity, $referenceType, $referenceId)
    {
        return match($this->method) {
            'fifo' => $this->consumeFIFO($quantity, $referenceType, $referenceId),
            'lifo' => $this->consumeLIFO($quantity, $referenceType, $referenceId),
            'weighted_average' => $this->consumeWeightedAverage($quantity, $referenceType, $referenceId),
            default => throw new \Exception("Unsupported valuation method"),
        };
    }

    protected function consumeFIFO($quantity, $referenceType, $referenceId)
    {
        $layers = InventoryLayer::where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_date', 'asc')
            ->get();

        $remaining = $quantity;
        $totalCost = 0;

        DB::transaction(function () use ($layers, &$remaining, &$totalCost, $referenceType, $referenceId) {
            foreach ($layers as $layer) {
                if ($remaining <= 0) break;
                $consume = min($remaining, $layer->remaining_quantity);
                $cost = $consume * $layer->unit_cost;
                $totalCost += $cost;

                $layer->remaining_quantity -= $consume;
                $layer->save();

                InventoryTransaction::create([
                    'transaction_id' => (string) \Str::uuid(),
                    'type' => 'consumption',
                    'product_id' => $this->productId,
                    'warehouse_id' => $this->warehouseId,
                    'batch_id' => $layer->batch_id,
                    'quantity' => $consume,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $cost,
                    'direction' => 'out',
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'metadata' => ['layer_id' => $layer->id, 'valuation_method' => 'fifo'],
                ]);

                $remaining -= $consume;
            }
        });

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock to consume {$quantity} units");
        }

        return $totalCost;
    }

    protected function consumeLIFO($quantity, $referenceType, $referenceId)
    {
        $layers = InventoryLayer::where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_date', 'desc')
            ->get();

        // ... similar to FIFO but order reversed
    }

    protected function consumeWeightedAverage($quantity, $referenceType, $referenceId)
    {
        $totalQty = InventoryLayer::where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->sum('remaining_quantity');
        $totalValue = InventoryLayer::where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->sum(DB::raw('remaining_quantity * unit_cost'));

        $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;
        $totalCost = $quantity * $avgCost;

        // Reduce all layers proportionally (simplified)
        $layers = InventoryLayer::where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->get();

        foreach ($layers as $layer) {
            $layer->remaining_quantity -= $layer->remaining_quantity * ($quantity / $totalQty);
            $layer->save();
        }

        InventoryTransaction::create([
            'transaction_id' => (string) \Str::uuid(),
            'type' => 'consumption',
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'quantity' => $quantity,
            'unit_cost' => $avgCost,
            'total_cost' => $totalCost,
            'direction' => 'out',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => ['valuation_method' => 'weighted_average'],
        ]);

        return $totalCost;
    }

    public function addLayer($batchId, $quantity, $unitCost, $receivedDate, $expiryDate, $referenceType, $referenceId)
    {
        return InventoryLayer::create([
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'batch_id' => $batchId,
            'received_date' => $receivedDate,
            'expiry_date' => $expiryDate,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }
}