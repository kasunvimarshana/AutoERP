<?php

namespace App\Services\Inventory;

use App\Models\InventoryLayer;
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
                $totalCost += $consume * $layer->unit_cost;

                $layer->remaining_quantity -= $consume;
                $layer->save();

                // record transaction
                $this->recordTransaction($layer, $consume, $referenceType, $referenceId);
                $remaining -= $consume;
            }
        });

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock to consume {$quantity}");
        }

        return $totalCost;
    }

    // LIFO (orderBy received_date desc) and weighted average methods follow similarly.
}