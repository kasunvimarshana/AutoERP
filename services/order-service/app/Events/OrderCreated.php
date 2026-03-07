<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
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
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event'        => 'order.created',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_id'  => $this->order->customer_id,
            'status'       => $this->order->status,
            'total_amount' => (float) $this->order->total_amount,
            'items'        => $this->order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->toArray(),
            'placed_at'    => $this->order->placed_at?->toIso8601String(),
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
