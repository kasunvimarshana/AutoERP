<?php

namespace App\Listeners;

use App\Events\StockMovementRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CheckReorderLevel implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(StockMovementRecorded $event): void
    {
        $stockLedger = $event->stockLedger;
        $product = $stockLedger->product;

        // Check if product has reorder level set
        if (! $product->reorder_level) {
            return;
        }

        // Get current balance from the stock ledger
        $currentBalance = $stockLedger->running_balance;

        // If stock is at or below reorder level, trigger notification
        if ($currentBalance <= $product->reorder_level) {
            Log::info("Product {$product->name} is below reorder level", [
                'product_id' => $product->id,
                'current_stock' => $currentBalance,
                'reorder_level' => $product->reorder_level,
            ]);

            // TODO: Dispatch notification to procurement team
            // event(new LowStockAlert($product, $currentBalance));
        }
    }
}
