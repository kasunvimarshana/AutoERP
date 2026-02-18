<?php

namespace App\Events;

use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {}
}
