<?php

namespace App\Events;

use App\Models\Order;

class OrderStatusChangedEvent extends BaseEvent
{
    public function __construct(
        public readonly Order  $order,
        public readonly string $previousStatus,
        public readonly string $newStatus
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'order.status_changed';
    }

    protected function toData(): array
    {
        return [
            'order_id'        => $this->order->id,
            'order_number'    => $this->order->order_number,
            'user_id'         => $this->order->user_id,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->newStatus,
            'changed_at'      => now()->toIso8601String(),
        ];
    }
}
