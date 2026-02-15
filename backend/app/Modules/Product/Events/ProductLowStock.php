<?php

declare(strict_types=1);

namespace App\Modules\Product\Events;

use App\Core\Events\BaseEvent;
use App\Modules\Product\Models\Product;

/**
 * Product Low Stock Event
 * 
 * Fired when a product stock falls below reorder level
 */
class ProductLowStock extends BaseEvent
{
    public readonly Product $product;
    public readonly float $currentStock;
    public readonly float $reorderLevel;
    public readonly ?int $branchId;

    /**
     * Create a new event instance
     *
     * @param Product $product
     * @param float $currentStock
     * @param float $reorderLevel
     * @param int|null $branchId
     * @param int|null $userId
     * @param array|null $metadata
     */
    public function __construct(
        Product $product,
        float $currentStock,
        float $reorderLevel,
        ?int $branchId = null,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($product->tenant_id, $userId, $metadata);
        $this->product = $product;
        $this->currentStock = $currentStock;
        $this->reorderLevel = $reorderLevel;
        $this->branchId = $branchId;
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
            'current_stock' => $this->currentStock,
            'reorder_level' => $this->reorderLevel,
            'branch_id' => $this->branchId,
        ]);
    }
}
