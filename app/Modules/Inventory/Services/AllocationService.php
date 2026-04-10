<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Core\Interfaces\AllocationInterface;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\InventoryReservation;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class AllocationService implements AllocationInterface
{
    protected $strategies = [];
    
    public function __construct()
    {
        $this->strategies = [
            'fifo' => new FifoAllocation(),
            'fefo' => new FefoAllocation(),
            'lifo' => new LifoAllocation(),
            'nearest_expiry' => new NearestExpiryAllocation(),
        ];
    }
    
    public function allocate($product, $quantity, $orderId, $warehouseId = null, $preferences = [])
    {
        $strategy = $product->allocation_algorithm ?? config('inventory.default_allocation_algorithm', 'fifo');
        $allocator = $this->strategies[$strategy] ?? $this->strategies['fifo'];
        
        return $allocator->allocate($product, $quantity, $orderId, $warehouseId, $preferences);
    }
    
    public function release($reservation)
    {
        DB::transaction(function () use ($reservation) {
            $balance = InventoryBalance::where([
                'product_id' => $reservation->product_id,
                'warehouse_id' => $reservation->warehouse_id,
                'batch_id' => $reservation->batch_id,
            ])->first();
            
            if ($balance) {
                $balance->quantity_reserved -= $reservation->allocated_quantity;
                $balance->save();
            }
            
            $reservation->status = 'cancelled';
            $reservation->save();
        });
    }
    
    public function getAvailableStock($product, $warehouseId = null, $strategy = 'fifo')
    {
        $allocator = $this->strategies[$strategy] ?? $this->strategies['fifo'];
        return $allocator->getAvailableStock($product, $warehouseId);
    }
}

// Allocation Strategy Implementations
class FifoAllocation
{
    public function allocate($product, $quantity, $orderId, $warehouseId, $preferences)
    {
        $stock = $this->getAvailableStock($product, $warehouseId);
        return $this->createReservations($stock, $quantity, $orderId, $warehouseId);
    }
    
    public function getAvailableStock($product, $warehouseId)
    {
        return InventoryBalance::where('product_id', $product->id)
            ->where('quantity_available', '>', 0)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    protected function createReservations($stock, $quantity, $orderId, $warehouseId)
    {
        $remaining = $quantity;
        $reservations = [];
        
        foreach ($stock as $item) {
            if ($remaining <= 0) break;
            
            $reserveQty = min($item->quantity_available, $remaining);
            
            $reservation = InventoryReservation::create([
                'order_id' => $orderId,
                'order_type' => 'sales_order',
                'product_id' => $item->product_id,
                'warehouse_id' => $item->warehouse_id,
                'batch_id' => $item->batch_id,
                'quantity' => $reserveQty,
                'allocated_quantity' => $reserveQty,
                'status' => 'allocated',
                'expires_at' => now()->addDays(config('inventory.reservation_expiry_days', 7)),
            ]);
            
            $item->quantity_reserved += $reserveQty;
            $item->save();
            
            $reservations[] = $reservation;
            $remaining -= $reserveQty;
        }
        
        if ($remaining > 0) {
            throw new \Exception("Insufficient stock to allocate {$quantity} units");
        }
        
        return $reservations;
    }
}

class FefoAllocation extends FifoAllocation
{
    public function getAvailableStock($product, $warehouseId)
    {
        return InventoryBalance::where('product_id', $product->id)
            ->where('quantity_available', '>', 0)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereHas('batch', fn($q) => $q->whereNotNull('expiry_date'))
            ->with('batch')
            ->get()
            ->sortBy(fn($item) => $item->batch->expiry_date);
    }
}

class LifoAllocation extends FifoAllocation
{
    public function getAvailableStock($product, $warehouseId)
    {
        return InventoryBalance::where('product_id', $product->id)
            ->where('quantity_available', '>', 0)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

class NearestExpiryAllocation extends FefoAllocation
{
    // Same as FEFO for allocation
}