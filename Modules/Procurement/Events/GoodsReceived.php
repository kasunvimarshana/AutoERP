<?php

namespace Modules\Procurement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Entities\GoodsReceipt;

/**
 * Goods Received Event
 *
 * Fired when goods are received.
 */
class GoodsReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public GoodsReceipt $goodsReceipt
    ) {}
}
