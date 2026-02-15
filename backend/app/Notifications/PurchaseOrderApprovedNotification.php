<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PurchaseOrderApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PurchaseOrder $purchaseOrder
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'purchase_order_approved',
            'purchase_order_id' => $this->purchaseOrder->id,
            'purchase_order_number' => $this->purchaseOrder->po_number,
            'vendor_name' => $this->purchaseOrder->vendor?->name ?? 'Unknown',
            'total_amount' => $this->purchaseOrder->total_amount,
            'currency_code' => $this->purchaseOrder->currency_code,
            'title' => 'Purchase Order Approved',
            'message' => "Purchase Order #{$this->purchaseOrder->po_number} has been approved. Vendor: {$this->purchaseOrder->vendor?->name}. Amount: {$this->purchaseOrder->currency_code} {$this->purchaseOrder->total_amount}",
            'action_url' => "/procurement/purchase-orders/{$this->purchaseOrder->id}",
            'severity' => 'success',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
