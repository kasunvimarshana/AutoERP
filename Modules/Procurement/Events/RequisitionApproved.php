<?php

namespace Modules\Procurement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Entities\PurchaseRequisition;

/**
 * Requisition Approved Event
 *
 * Fired when a purchase requisition is approved.
 */
class RequisitionApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PurchaseRequisition $requisition
    ) {}
}
