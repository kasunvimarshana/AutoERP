<?php

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Services\NotificationService;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Notifications\StockAdjustmentNotification;
use Modules\IAM\Models\User;

class SendStockAdjustmentNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(StockAdjusted $event): void
    {
        try {
            // Only notify for significant adjustments (absolute value > threshold)
            $threshold = config('inventory.notification_threshold', 10);
            if (abs($event->stockLedger->quantity) < $threshold) {
                return; // Skip minor adjustments
            }

            // Get users who should be notified
            $users = User::role(['admin', 'inventory_manager'])->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification
            $notification = new StockAdjustmentNotification(
                $event->product,
                $event->stockLedger,
                $event->stockLedger->transaction_type->value
            );

            $this->notificationService->send($users, $notification);

            Log::info('Stock adjustment notification sent', [
                'product_id' => $event->product->id,
                'product_name' => $event->product->name,
                'quantity' => $event->stockLedger->quantity,
                'user_count' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send stock adjustment notification', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
