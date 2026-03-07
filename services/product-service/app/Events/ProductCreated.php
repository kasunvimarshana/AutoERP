<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->product->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'product.created';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'sku'        => $this->product->sku,
            'name'       => $this->product->name,
            'tenant_id'  => $this->product->tenant_id,
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
