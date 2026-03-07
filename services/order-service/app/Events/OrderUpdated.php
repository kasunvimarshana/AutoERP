<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event'        => 'order.updated',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_id'  => $this->order->customer_id,
            'status'       => $this->order->status,
            'saga_status'  => $this->order->saga_status,
            'total_amount' => (float) $this->order->total_amount,
            'updated_at'   => $this->order->updated_at?->toIso8601String(),
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
