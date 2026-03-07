<?php

namespace App\Events;

use App\Models\Order;

class OrderCreatedEvent extends BaseEvent
{
    public function __construct(public readonly Order $order)
    {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'order.created';
    }

    protected function toData(): array
    {
        return [
            'order_id'       => $this->order->id,
            'order_number'   => $this->order->order_number,
            'user_id'        => $this->order->user_id,
            'total'          => $this->order->total,
            'status'         => $this->order->status,
            'payment_status' => $this->order->payment_status,
            'items_count'    => $this->order->items->count(),
        ];
    }
}
