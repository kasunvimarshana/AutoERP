<?php

declare(strict_types=1);

namespace App\Listeners\Procurement;

use App\Events\Procurement\PurchaseOrderApproved;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPurchaseOrderApproval implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PurchaseOrderApproved $event): void
    {
        $purchaseOrder = $event->purchaseOrder;
        
        // Notify the creator
        $creator = User::find($purchaseOrder->created_by);
        if ($creator) {
            $creator->notify(new \App\Notifications\PurchaseOrderApprovedNotification($purchaseOrder));
        }
        
        // Notify procurement team
        $procurementUsers = User::where('tenant_id', $event->tenantId)
            ->permission('manage-purchase-orders')
            ->where('id', '!=', $event->approvedBy)
            ->get();
            
        foreach ($procurementUsers as $user) {
            $user->notify(new \App\Notifications\PurchaseOrderApprovedNotification($purchaseOrder));
        }
        
        // Notify vendor if they have a user account
        if ($purchaseOrder->vendor && $purchaseOrder->vendor->email) {
            $vendorUser = User::where('email', $purchaseOrder->vendor->email)
                ->where('tenant_id', $event->tenantId)
                ->first();
                
            if ($vendorUser) {
                $vendorUser->notify(new \App\Notifications\PurchaseOrderApprovedNotification($purchaseOrder));
            }
        }
    }
}
