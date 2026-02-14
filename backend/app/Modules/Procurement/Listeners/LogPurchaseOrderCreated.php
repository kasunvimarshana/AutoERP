<?php

namespace App\Modules\Procurement\Listeners;

use App\Modules\Procurement\Events\PurchaseOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPurchaseOrderCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     *
     * @param PurchaseOrderCreated $event
     * @return void
     */
    public function handle(PurchaseOrderCreated $event): void
    {
        Log::info('Purchase Order created', [
            'purchase_order_id' => $event->purchaseOrder->id,
            'po_number' => $event->purchaseOrder->po_number,
            'supplier_id' => $event->purchaseOrder->supplier_id,
            'tenant_id' => $event->purchaseOrder->tenant_id,
        ]);
    }
}
