<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\InventoryReservation;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    /**
     * Allocate stock for a sales order.
     */
    public function allocate(Product $product, $quantity, $salesOrderId, $warehouseId = null, $preferences = [])
    {
        $strategy = $product->allocation_algorithm ?? config('inventory.default_allocation_algorithm', 'fifo');

        $availableStock = $this->getAvailableStock($product, $warehouseId, $strategy);
        $allocated = 0;

        DB::transaction(function () use ($availableStock, $quantity, $salesOrderId, &$allocated) {
            foreach ($availableStock as $stock) {
                if ($allocated >= $quantity) break;

                $remaining = $quantity - $allocated;
                $allocateQty = min($stock->quantity_available, $remaining);

                if ($allocateQty <= 0) continue;

                $reservation = new InventoryReservation([
                    'sales_order_id' => $salesOrderId,
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'batch_id' => $stock->batch_id,
                    'serial_number_id' => $stock->serial_number_id,
                    'quantity' => $allocateQty,
                    'expires_at' => now()->addDays(config('inventory.reservation_expiry_days', 7)),
                ]);
                $reservation->save();

                // Update inventory balance reserved quantity
                $stock->quantity_reserved += $allocateQty;
                $stock->save();

                $allocated += $allocateQty;
            }
        });

        if ($allocated < $quantity) {
            throw new \Exception("Unable to allocate full quantity for product {$product->id}");
        }

        return $allocated;
    }

    /**
     * Release reservations (e.g., when order is cancelled).
     */
    public function release(InventoryReservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            $balance = InventoryBalance::where([
                'product_id' => $reservation->product_id,
                'warehouse_id' => $reservation->warehouse_id,
                'batch_id' => $reservation->batch_id,
                'serial_number_id' => $reservation->serial_number_id,
            ])->first();

            if ($balance) {
                $balance->quantity_reserved -= $reservation->quantity;
                $balance->save();
            }

            $reservation->delete();
        });
    }

    private function getAvailableStock(Product $product, $warehouseId = null, $strategy = 'fifo')
    {
        $query = InventoryBalance::where('product_id', $product->id)
            ->where('quantity_available', '>', 0)
            ->with(['batch', 'serialNumber']); // to get expiry if needed

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Apply sorting based on strategy
        if ($strategy === 'fefo') {
            $query->join('batches', 'inventory_balances.batch_id', '=', 'batches.id')
                  ->orderBy('batches.expiry_date', 'asc');
        } elseif ($strategy === 'fifo') {
            $query->orderBy('created_at', 'asc');
        } elseif ($strategy === 'lifo') {
            $query->orderBy('created_at', 'desc');
        } elseif ($strategy === 'nearest_expiry') {
            $query->join('batches', 'inventory_balances.batch_id', '=', 'batches.id')
                  ->orderBy('batches.expiry_date', 'asc');
        }

        return $query->get();
    }
}