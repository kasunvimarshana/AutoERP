<?php

declare(strict_types=1);

namespace App\Events\Product;

use App\Core\Events\BaseEvent;
use App\Modules\Product\Models\Product;

/**
 * Product Price Changed Event
 * 
 * Dispatched when a product's price is changed
 */
class ProductPriceChanged extends BaseEvent
{
    public function __construct(
        public readonly Product $product,
        public readonly float $oldBuyingPrice,
        public readonly float $newBuyingPrice,
        public readonly float $oldSellingPrice,
        public readonly float $newSellingPrice,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    /**
     * Get event payload for logging
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'old_buying_price' => $this->oldBuyingPrice,
            'new_buying_price' => $this->newBuyingPrice,
            'old_selling_price' => $this->oldSellingPrice,
            'new_selling_price' => $this->newSellingPrice,
        ]);
    }
}
