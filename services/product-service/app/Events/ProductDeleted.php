<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $productData Snapshot of the product before deletion
     */
    public function __construct(
        public readonly int $productId,
        public readonly array $productData = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('products'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'product.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'event'        => 'product.deleted',
            'product_id'   => $this->productId,
            'product_data' => $this->productData,
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
