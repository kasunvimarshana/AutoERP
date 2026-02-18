<?php

namespace Modules\Procurement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Entities\Supplier;

/**
 * Supplier Rated Event
 *
 * Fired when a supplier is rated.
 */
class SupplierRated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Supplier $supplier
    ) {}
}
