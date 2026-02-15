<?php

declare(strict_types=1);

namespace App\Modules\Product\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Modules\Product\Models\Product;

/**
 * Low Stock Alert Notification
 * 
 * Sent when product stock falls below reorder level
 */
class LowStockAlert extends BaseNotification
{
    /**
     * Create a new notification instance
     *
     * @param Product $product
     * @param float $currentStock
     * @param float $reorderLevel
     */
    public function __construct(
        public readonly Product $product,
        public readonly float $currentStock,
        public readonly float $reorderLevel
    ) {
        $this->setTitle('Low Stock Alert')
            ->setMessage(sprintf(
                'Product "%s" is running low on stock. Current: %.2f, Reorder Level: %.2f',
                $this->product->name,
                $this->currentStock,
                $this->reorderLevel
            ))
            ->setType('warning')
            ->setAction("/products/{$this->product->id}", 'View Product')
            ->setMetadata([
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'product_sku' => $this->product->sku,
                'current_stock' => $this->currentStock,
                'reorder_level' => $this->reorderLevel,
            ]);
    }
}
