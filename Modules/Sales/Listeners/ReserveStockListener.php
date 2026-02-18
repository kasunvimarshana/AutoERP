<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Sales\Events\SalesOrderConfirmed;

/**
 * Reserve Stock Listener
 *
 * Handles stock reservation when a sales order is confirmed.
 * Implements event-driven integration between Sales and Inventory modules.
 */
class ReserveStockListener implements ShouldQueue
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
     * Create a new listener instance
     */
    public function __construct(
        private StockMovementRepositoryInterface $stockMovementRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(SalesOrderConfirmed $event): void
    {
        DB::beginTransaction();
        try {
            $order = $event->salesOrder;

            // Reserve stock for each order line
            foreach ($order->lines as $line) {
                if (! $line->product_id) {
                    continue;
                }

                // Create stock reservation movement
                $this->stockMovementRepository->create([
                    'tenant_id' => $order->tenant_id,
                    'product_id' => $line->product_id,
                    'warehouse_id' => $order->warehouse_id ?? null,
                    'quantity' => -$line->quantity, // Negative for reservation
                    'movement_type' => 'RESERVE',
                    'reference_type' => 'sales_order',
                    'reference_id' => $order->id,
                    'reference_number' => $order->order_number,
                    'notes' => "Stock reserved for sales order {$order->order_number}",
                    'status' => 'completed',
                ]);
            }

            DB::commit();

            Log::info('Stock reserved for sales order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'lines_count' => $order->lines->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reserve stock for sales order', [
                'order_id' => $event->salesOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(SalesOrderConfirmed $event, \Throwable $exception): void
    {
        Log::error('Stock reservation failed permanently', [
            'order_id' => $event->salesOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
