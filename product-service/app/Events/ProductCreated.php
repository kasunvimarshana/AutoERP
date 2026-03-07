<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ProductCreated Event
 *
 * Fired after a product is successfully created within a database transaction.
 * Listeners publish this event to RabbitMQ so that other services
 * (e.g., Inventory Service) can react and create related records.
 */
class ProductCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Product $product
    ) {}

    /**
     * Get the event payload for cross-service messaging.
     * This standardized payload is published to RabbitMQ.
     */
    public function toPayload(): array
    {
        return [
            'event'      => 'product.created',
            'product_id' => $this->product->id,
            'name'       => $this->product->name,
            'sku'        => $this->product->sku,
            'price'      => $this->product->price,
            'stock'      => $this->product->stock,
            'category'   => $this->product->category,
            'is_active'  => $this->product->is_active,
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
