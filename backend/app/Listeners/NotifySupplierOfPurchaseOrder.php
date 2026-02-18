<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifySupplierOfPurchaseOrder implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderApproved $event): void
    {
        $purchaseOrder = $event->purchaseOrder->load('supplier', 'items.product');

        Log::info('Purchase order approved - notify supplier', [
            'po_number' => $purchaseOrder->po_number,
            'supplier' => $purchaseOrder->supplier->name,
            'total_amount' => $purchaseOrder->total_amount,
        ]);

        // TODO: Send email notification to supplier
        // Mail::to($purchaseOrder->supplier->email)
        //     ->send(new PurchaseOrderNotification($purchaseOrder));
    }
}
