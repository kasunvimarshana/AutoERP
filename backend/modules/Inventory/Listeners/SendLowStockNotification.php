<?php

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Services\NotificationService;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Notifications\LowStockNotification;
use Modules\IAM\Models\User;

class SendLowStockNotification
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
    public function handle(LowStockAlert $event): void
    {
        try {
            // Get users who should be notified (e.g., inventory managers, admins)
            $users = User::role(['admin', 'inventory_manager'])->get();

            if ($users->isEmpty()) {
                Log::warning('No users found to notify for low stock alert', [
                    'product_id' => $event->product->id,
                    'product_name' => $event->product->name,
                ]);
                return;
            }

            // Send notification
            $notification = new LowStockNotification(
                $event->product,
                $event->warehouseId,
                $event->currentStock,
                $event->product->reorder_level ?? 0
            );

            $this->notificationService->send($users, $notification);

            Log::info('Low stock notification sent', [
                'product_id' => $event->product->id,
                'product_name' => $event->product->name,
                'warehouse_id' => $event->warehouseId,
                'current_stock' => $event->currentStock,
                'user_count' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send low stock notification', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
