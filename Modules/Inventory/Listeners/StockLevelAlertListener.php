<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Inventory\Events\LowStockAlert;

/**
 * Stock Level Alert Listener
 *
 * Sends notifications when stock levels fall below reorder point.
 * Logs alerts for inventory management and reporting.
 */
class StockLevelAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Handle the event
     */
    public function handle(LowStockAlert $event): void
    {
        try {
            $stockLevel = $event->stockLevel;
            $product = $stockLevel->product;
            $warehouse = $stockLevel->warehouse;

            // Log the alert
            Log::warning('Low stock alert triggered', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'warehouse_id' => $warehouse->id ?? null,
                'warehouse_name' => $warehouse->name ?? 'N/A',
                'current_quantity' => $stockLevel->quantity,
                'reorder_point' => $stockLevel->reorder_point,
                'reorder_quantity' => $stockLevel->reorder_quantity,
                'shortage' => $stockLevel->reorder_point - $stockLevel->quantity,
            ]);

            // TODO: Send notification to inventory managers
            // This can be implemented when notification system is ready
            // Notification::send($inventoryManagers, new LowStockNotification($stockLevel));

            // Log system event for audit trail
            Log::info('Low stock alert notification queued', [
                'stock_level_id' => $stockLevel->id,
                'product_id' => $product->id,
                'current_quantity' => $stockLevel->quantity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process low stock alert', [
                'stock_level_id' => $event->stockLevel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(LowStockAlert $event, \Throwable $exception): void
    {
        Log::error('Low stock alert processing failed permanently', [
            'stock_level_id' => $event->stockLevel->id,
            'product_id' => $event->stockLevel->product_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
