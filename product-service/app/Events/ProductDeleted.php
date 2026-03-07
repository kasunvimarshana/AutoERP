<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ProductDeleted Event
 *
 * Fired after a product is successfully deleted within a database transaction.
 * Listeners publish this event to RabbitMQ so that other services
 * (e.g., Inventory Service) can delete related inventory records,
 * ensuring cross-service data consistency.
 */
class ProductDeleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int    $productId   ID of the deleted product
     * @param  string $productName Name of the deleted product (for cross-service lookup)
     * @param  string $sku         SKU of the deleted product
     */
    public function __construct(
        public readonly int    $productId,
        public readonly string $productName,
        public readonly ?string $sku
    ) {}

    /**
     * Get the event payload for cross-service messaging.
     */
    public function toPayload(): array
    {
        return [
            'event'      => 'product.deleted',
            'product_id' => $this->productId,
            'name'       => $this->productName,
            'sku'        => $this->sku,
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
