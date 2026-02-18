<?php

namespace Modules\Procurement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Entities\PurchaseOrder;

/**
 * Purchase Order Created Event
 *
 * Fired when a purchase order is created.
 */
class PurchaseOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {}
}
