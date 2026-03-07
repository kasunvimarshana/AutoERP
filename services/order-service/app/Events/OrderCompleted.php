<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCompleted implements ShouldBroadcast
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
        return 'order.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'event'        => 'order.completed',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_id'  => $this->order->customer_id,
            'total_amount' => (float) $this->order->total_amount,
            'items'        => $this->order->items->map(fn ($item) => [
                'product_id'  => $item->product_id,
                'product_name' => $item->product_name,
                'quantity'    => $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->toArray(),
            'delivered_at' => $this->order->delivered_at?->toIso8601String(),
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
