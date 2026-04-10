<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryValuationLayer;
use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class ValuationService
{
    /**
     * Get cost for a product using its configured method.
     */
    public function getCost(Product $product, $quantity, $warehouseId = null)
    {
        $method = $product->valuation_method ?? config('inventory.default_valuation_method', 'fifo');
        return $this->{$method . 'Cost'}($product, $quantity, $warehouseId);
    }

    /**
     * Add a new inbound layer.
     */
    public function addLayer(InventoryTransaction $transaction, $unitCost)
    {
        $product = $transaction->product;
        $layer = new InventoryValuationLayer([
            'product_id' => $product->id,
            'warehouse_id' => $transaction->warehouse_id,
            'layer_type' => $this->determineLayerType($transaction),
            'quantity' => $transaction->quantity,
            'unit_cost' => $unitCost,
            'remaining_quantity' => $transaction->quantity,
            'transaction_id' => $transaction->id,
        ]);
        $layer->save();

        $transaction->valuation_layer_id = $layer->id;
        $transaction->save();
    }

    /**
     * Consume layers for outbound transaction.
     */
    public function consumeLayers(Product $product, $quantity, $warehouseId = null, $strategy = null)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId, $strategy);
        $remaining = $quantity;
        $consumedCost = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $consumeQty = min($layer->remaining_quantity, $remaining);
            $consumedCost += $consumeQty * $layer->unit_cost;

            $layer->remaining_quantity -= $consumeQty;
            $layer->save();

            $remaining -= $consumeQty;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock to consume {$quantity} for product {$product->id}");
        }

        return $consumedCost;
    }

    private function getOrderedLayers($product, $warehouseId, $strategy = null)
    {
        $query = InventoryValuationLayer::where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $strategy = $strategy ?? $product->stock_rotation_strategy ?? config('inventory.default_rotation_strategy', 'fifo');

        if ($strategy === 'fifo') {
            $query->orderBy('created_at', 'asc');
        } elseif ($strategy === 'lifo') {
            $query->orderBy('created_at', 'desc');
        } // else FEFO would require expiry date on layers

        return $query->get();
    }

    private function determineLayerType(InventoryTransaction $transaction)
    {
        if (in_array($transaction->transaction_type, ['purchase', 'return'])) {
            return $transaction->transaction_type === 'purchase' ? 'purchase' : 'return';
        }
        return 'adjustment';
    }

    // Methods for valuation types
    private function fifoCost($product, $quantity, $warehouseId)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId, 'fifo');
        $totalCost = 0;
        $remaining = $quantity;

        foreach ($layers as $layer) {
            $consume = min($layer->remaining_quantity, $remaining);
            $totalCost += $consume * $layer->unit_cost;
            $remaining -= $consume;
            if ($remaining <= 0) break;
        }

        return $totalCost / $quantity;
    }

    private function lifoCost($product, $quantity, $warehouseId)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId, 'lifo');
        $totalCost = 0;
        $remaining = $quantity;

        foreach ($layers as $layer) {
            $consume = min($layer->remaining_quantity, $remaining);
            $totalCost += $consume * $layer->unit_cost;
            $remaining -= $consume;
            if ($remaining <= 0) break;
        }

        return $totalCost / $quantity;
    }

    private function weightedAvgCost($product, $quantity, $warehouseId)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId);
        $totalQty = 0;
        $totalCost = 0;

        foreach ($layers as $layer) {
            $totalQty += $layer->remaining_quantity;
            $totalCost += $layer->remaining_quantity * $layer->unit_cost;
        }

        $avgCost = $totalQty > 0 ? $totalCost / $totalQty : 0;
        return $avgCost;
    }
}