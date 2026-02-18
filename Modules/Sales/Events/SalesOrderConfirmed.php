<?php

namespace Modules\Sales\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Entities\SalesOrder;

/**
 * Sales Order Confirmed Event
 *
 * Fired when a sales order is confirmed.
 * Other modules can listen to this event and react accordingly.
 */
class SalesOrderConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public SalesOrder $salesOrder
    ) {}
}
