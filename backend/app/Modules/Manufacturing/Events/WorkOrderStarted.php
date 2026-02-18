<?php

namespace App\Modules\Manufacturing\Events;

use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Work Order Started Event
 *
 * Dispatched when a work order is started.
 */
class WorkOrderStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public WorkOrder $workOrder
    ) {}
}
