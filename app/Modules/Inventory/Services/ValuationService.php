<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Core\Interfaces\ValuationInterface;
use App\Modules\Inventory\Models\InventoryValuationLayer;
use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class ValuationService implements ValuationInterface
{
    protected $strategies = [];
    
    public function __construct()
    {
        $this->strategies = [
            'fifo' => new FifoValuation(),
            'lifo' => new LifoValuation(),
            'weighted_avg' => new WeightedAverageValuation(),
            'specific' => new SpecificIdentificationValuation(),
        ];
    }
    
    public function calculateCost($product, $quantity, $warehouseId = null)
    {
        $method = $product->valuation_method ?? config('inventory.default_valuation_method', 'fifo');
        $strategy = $this->strategies[$method] ?? $this->strategies['fifo'];
        
        return $strategy->calculate($product, $quantity, $warehouseId);
    }
    
    public function addLayer($transaction, $unitCost)
    {
        DB::transaction(function () use ($transaction, $unitCost) {
            $layer = InventoryValuationLayer::create([
                'product_id' => $transaction->product_id,
                'warehouse_id' => $transaction->to_warehouse_id ?? $transaction->from_warehouse_id,
                'batch_id' => $transaction->batch_id,
                'layer_type' => $this->determineLayerType($transaction),
                'quantity' => $transaction->quantity,
                'remaining_quantity' => $transaction->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $transaction->quantity * $unitCost,
                'transaction_id' => $transaction->id,
                'layer_date' => $transaction->transaction_date,
                'expiry_date' => $transaction->batch_id ? $transaction->batch->expiry_date : null,
            ]);
            
            $transaction->valuation_layer_id = $layer->id;
            $transaction->save();
        });
    }
    
    public function consumeLayers($product, $quantity, $warehouseId = null, $strategy = null)
    {
        $method = $product->valuation_method ?? config('inventory.default_valuation_method', 'fifo');
        $strategy = $this->strategies[$method] ?? $this->strategies['fifo'];
        
        return $strategy->consume($product, $quantity, $warehouseId);
    }
    
    protected function determineLayerType($transaction)
    {
        $map = [
            'purchase' => 'purchase',
            'return_in' => 'return',
            'production' => 'production',
            'adjustment' => 'adjustment',
            'transfer' => 'transfer',
        ];
        
        return $map[$transaction->transaction_type] ?? 'adjustment';
    }
}

// Strategy Pattern Implementations
class FifoValuation
{
    public function calculate($product, $quantity, $warehouseId)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId, 'asc');
        return $this->calculateCostFromLayers($layers, $quantity);
    }
    
    public function consume($product, $quantity, $warehouseId)
    {
        $layers = $this->getOrderedLayers($product, $warehouseId, 'asc');
        return $this->consumeFromLayers($layers, $quantity);
    }
    
    protected function getOrderedLayers($product, $warehouseId, $order)
    {
        return InventoryValuationLayer::where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('layer_date', $order)
            ->get();
    }
    
    protected function calculateCostFromLayers($layers, $quantity)
    {
        $remaining = $quantity;
        $totalCost = 0;
        
        foreach ($layers as $layer) {
            $consume = min($layer->remaining_quantity, $remaining);
            $totalCost += $consume * $layer->unit_cost;
            $remaining -= $consume;
            if ($remaining <= 0) break;
        }
        
        return $quantity > 0 ? $totalCost / $quantity : 0;
    }
    
    protected function consumeFromLayers($layers, $quantity)
    {
        $remaining = $quantity;
        $consumedCost = 0;
        
        foreach ($layers as $layer) {
            if ($remaining <= 0) break;
            
            $consume = min($layer->remaining_quantity, $remaining);
            $consumedCost += $consume * $layer->unit_cost;
            
            $layer->remaining_quantity -= $consume;
            $layer->save();
            
            $remaining -= $consume;
        }
        
        if ($remaining > 0) {
            throw new \Exception("Insufficient stock to consume {$quantity} units");
        }
        
        return $consumedCost;
    }
}

class LifoValuation extends FifoValuation
{
    protected function getOrderedLayers($product, $warehouseId, $order)
    {
        return parent::getOrderedLayers($product, $warehouseId, 'desc');
    }
}

class WeightedAverageValuation
{
    public function calculate($product, $quantity, $warehouseId)
    {
        $layers = InventoryValuationLayer::where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->get();
        
        $totalQty = $layers->sum('remaining_quantity');
        $totalCost = $layers->sum(fn($layer) => $layer->remaining_quantity * $layer->unit_cost);
        
        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }
    
    public function consume($product, $quantity, $warehouseId)
    {
        // Weighted average doesn't require consuming specific layers
        // Just update balances, no layer consumption needed
        return $this->calculate($product, $quantity, $warehouseId) * $quantity;
    }
}

class SpecificIdentificationValuation
{
    public function calculate($product, $quantity, $warehouseId)
    {
        // For specific identification, cost is determined per batch/serial
        // This method should be called with specific batch/serial information
        throw new \Exception("Specific identification requires batch/serial selection");
    }
    
    public function consume($product, $quantity, $warehouseId)
    {
        // Consumption handled via specific batch/serial selection
        return 0;
    }
}