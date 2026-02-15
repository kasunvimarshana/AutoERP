<?php

declare(strict_types=1);

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductLowStock;
use App\Modules\Product\Notifications\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

/**
 * Send Low Stock Notification Listener
 * 
 * Handles ProductLowStock event and sends notifications
 */
class SendLowStockNotification implements ShouldQueue
{
    /**
     * Handle the event
     *
     * @param ProductLowStock $event
     * @return void
     */
    public function handle(ProductLowStock $event): void
    {
        try {
            // Log the event
            Log::info('Low stock detected', $event->toArray());

            // Get users who should be notified (admins, managers)
            $users = User::where('tenant_id', $event->tenantId)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['super-admin', 'admin', 'manager']);
                })
                ->get();

            // Send notification to users
            foreach ($users as $user) {
                $user->notify(new LowStockAlert(
                    $event->product,
                    $event->currentStock,
                    $event->reorderLevel
                ));
            }

            Log::info('Low stock notifications sent', [
                'product_id' => $event->product->id,
                'users_notified' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send low stock notification', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if the listener should be queued
     *
     * @param ProductLowStock $event
     * @return bool
     */
    public function shouldQueue(ProductLowStock $event): bool
    {
        return true;
    }
}
