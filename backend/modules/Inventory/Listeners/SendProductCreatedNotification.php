<?php

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Services\NotificationService;
use Modules\Inventory\Events\ProductCreated;
use Modules\Inventory\Notifications\ProductCreatedNotification;
use Modules\IAM\Models\User;

class SendProductCreatedNotification
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
    public function handle(ProductCreated $event): void
    {
        try {
            // Get users who should be notified
            $users = User::role(['admin', 'inventory_manager'])->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification
            $notification = new ProductCreatedNotification($event->product);

            $this->notificationService->send($users, $notification);

            Log::info('Product created notification sent', [
                'product_id' => $event->product->id,
                'product_name' => $event->product->name,
                'user_count' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send product created notification', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
