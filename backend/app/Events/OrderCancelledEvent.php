<?php

namespace App\Events;

use App\Models\Order;

class OrderCancelledEvent extends BaseEvent
{
    public function __construct(
        public readonly Order  $order,
        public readonly string $reason = ''
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'order.cancelled';
    }

    protected function toData(): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'user_id'      => $this->order->user_id,
            'total'        => $this->order->total,
            'reason'       => $this->reason,
            'cancelled_at' => now()->toIso8601String(),
        ];
    }
}
