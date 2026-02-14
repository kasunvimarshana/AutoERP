<?php

namespace App\Modules\Procurement\Listeners;

use App\Modules\Procurement\Events\PurchaseOrderUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPurchaseOrderUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     *
     * @param PurchaseOrderUpdated $event
     * @return void
     */
    public function handle(PurchaseOrderUpdated $event): void
    {
        Log::info('Purchase Order updated', [
            'purchase_order_id' => $event->purchaseOrder->id,
            'po_number' => $event->purchaseOrder->po_number,
            'status' => $event->purchaseOrder->status,
            'tenant_id' => $event->purchaseOrder->tenant_id,
        ]);
    }
}
