<?php

declare(strict_types=1);

namespace App\Modules\Product\Events;

use App\Core\Events\BaseEvent;
use App\Modules\Product\Models\Product;

/**
 * Product Created Event
 * 
 * Fired when a new product is created
 */
class ProductCreated extends BaseEvent
{
    public readonly Product $product;

    /**
     * Create a new event instance
     *
     * @param Product $product
     * @param int|null $userId
     * @param array|null $metadata
     */
    public function __construct(
        Product $product,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($product->tenant_id, $userId, $metadata);
        $this->product = $product;
    }

    /**
     * Get event payload
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'product_type' => $this->product->type,
        ]);
    }
}
