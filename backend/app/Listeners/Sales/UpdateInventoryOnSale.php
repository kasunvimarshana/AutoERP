<?php

declare(strict_types=1);

namespace App\Listeners\Sales;

use App\Events\Sales\OrderFulfilled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateInventoryOnSale implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderFulfilled $event): void
    {
        // This is handled through stock ledgers in the service layer
        // This listener can be used for additional processing like:
        // - Updating inventory statistics
        // - Triggering reorder points
        // - Updating product availability status
        
        DB::transaction(function () use ($event) {
            // Load order items with tenant scoping
            $event->order->load('items.product');
            
            foreach ($event->order->items as $item) {
                // Additional inventory processing logic
                // Example: Check if reorder point reached
                $product = $item->product;
                
                if ($product && $product->current_stock <= $product->reorder_level) {
                    // Dispatch low stock event
                    event(new \App\Events\Inventory\LowStockDetected(
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->current_stock,
                        $product->reorder_level,
                        $event->order->location_id ?? $event->order->branch_id,
                        $event->tenantId
                    ));
                }
            }
        });
    }
}
