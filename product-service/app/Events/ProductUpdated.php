<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ProductUpdated Event
 *
 * Fired after a product is successfully updated within a database transaction.
 * Listeners publish this event to RabbitMQ so that other services
 * (e.g., Inventory Service) can sync product name / price changes.
 */
class ProductUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Product $product,
        public readonly array   $previousData
    ) {}

    /**
     * Get the event payload for cross-service messaging.
     * Includes previous data so consumers can detect what changed.
     */
    public function toPayload(): array
    {
        return [
            'event'         => 'product.updated',
            'product_id'    => $this->product->id,
            'name'          => $this->product->name,
            'sku'           => $this->product->sku,
            'price'         => $this->product->price,
            'stock'         => $this->product->stock,
            'category'      => $this->product->category,
            'is_active'     => $this->product->is_active,
            'previous_name' => $this->previousData['name'] ?? null,
            'previous_sku'  => $this->previousData['sku'] ?? null,
            'timestamp'     => now()->toIso8601String(),
        ];
    }
}
