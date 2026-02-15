<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockExpiring;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendStockExpiryAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(StockExpiring $event): void
    {
        // Get users with permission to receive stock expiry alerts for this tenant
        $users = User::where('tenant_id', $event->tenantId)
            ->permission('receive-expiry-alerts')
            ->get();

        foreach ($users as $user) {
            $user->notify(new \App\Notifications\StockExpiryNotification(
                $event->productId,
                $event->productName,
                $event->productSku,
                $event->batchNumber,
                $event->quantity,
                $event->expiryDate,
                $event->daysUntilExpiry,
                $event->locationId
            ));
        }
    }
}
