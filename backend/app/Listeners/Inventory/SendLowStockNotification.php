<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\LowStockDetected;
use App\Notifications\LowStockNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLowStockNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LowStockDetected $event): void
    {
        // Get users with permission to receive low stock notifications for this tenant
        $users = User::where('tenant_id', $event->tenantId)
            ->permission('receive-low-stock-alerts')
            ->get();

        foreach ($users as $user) {
            $user->notify(new LowStockNotification(
                $event->productId,
                $event->productName,
                $event->productSku,
                $event->currentQuantity,
                $event->minimumQuantity,
                $event->locationId
            ));
        }
    }
}
