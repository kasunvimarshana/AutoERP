<?php

namespace App\Modules\Inventory\Sagas;

use Illuminate\Support\Facades\DB;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Models\InventoryLedger;
use Exception;
use Illuminate\Support\Facades\Log;

class ReserveInventoryForOrderSaga
{
    /**
     * Handle the OrderCreated event to trigger a local transaction.
     * If it fails, report failure back to the centralized message bus (compensating).
     */
    public function handle(array $orderPayload): void
    {
        $orderId = $orderPayload['order_id'];
        $productId = $orderPayload['product_id'];
        $quantityNeeded = $orderPayload['quantity'];

        DB::beginTransaction();

        try {
            // Lock the row to prevent race conditions
            $inventory = Inventory::where('product_id', $productId)->lockForUpdate()->firstOrFail();

            if ($inventory->available_stock < $quantityNeeded) {
                throw new Exception("Insufficient stock for Product ID: " . $productId);
            }

            // Perform ledger entry (append-only)
            InventoryLedger::create([
                'product_id' => $productId,
                'transaction_type' => 'RESERVE',
                'quantity_change' => -$quantityNeeded,
                'reference_id' => 'ORDER_' . $orderId,
            ]);

            // Update materialized view
            $inventory->available_stock -= $quantityNeeded;
            $inventory->reserved_stock += $quantityNeeded;
            $inventory->save();

            DB::commit();

            // SUCCESS -> Broadcast Event: InventoryReserved
            $this->broadcastSuccess($orderId, $productId);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SAGA FAILURE: Reserving Inventory for Order $orderId failed. Initiating Compensation.", [
                'error' => $e->getMessage()
            ]);

            // FAIL -> Broadcast Event: InventoryReservationFailed (Compensating Transaction triggers on order-service)
            $this->broadcastFailure($orderId, $e->getMessage());
        }
    }

    private function broadcastSuccess($orderId, $productId)
    {
        // Publish to RabbitMQ using Laravel Events or raw AMQP
        // e.g., event(new \App\Modules\Inventory\Events\InventoryReserved($orderId, $productId));
        Log::info("Publishing InventoryReserved for Order $orderId");
    }

    private function broadcastFailure($orderId, $reason)
    {
        // Publish to RabbitMQ to compensate (cancel the order locally in order-service)
        // e.g., event(new \App\Modules\Inventory\Events\InventoryReservationFailed($orderId, $reason));
        Log::info("Publishing InventoryReservationFailed for Order $orderId - $reason");
    }
}
