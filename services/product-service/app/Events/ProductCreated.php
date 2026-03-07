<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('products'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'product.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event'      => 'product.created',
            'product_id' => $this->product->id,
            'product'    => [
                'id'             => $this->product->id,
                'name'           => $this->product->name,
                'sku'            => $this->product->sku,
                'price'          => (float) $this->product->price,
                'category'       => $this->product->category,
                'status'         => $this->product->status,
                'stock_quantity' => $this->product->stock_quantity,
            ],
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
