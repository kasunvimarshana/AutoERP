<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory Item Model
 *
 * Represents the stock level of a product in a specific warehouse.
 * Tracks quantity_on_hand, quantity_reserved (Saga), and reorder_point.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $warehouse_id
 * @property int $quantity_on_hand   Total physical stock
 * @property int $quantity_reserved  Reserved by pending orders (Saga)
 * @property int $reorder_point      Threshold for low stock alerts
 * @property int $reorder_quantity   Amount to reorder when threshold hit
 */
class InventoryItem extends Model
{
    use HasUuids;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'reorder_point',
        'reorder_quantity',
        'location_code',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
    ];

    /**
     * Get the product this inventory item belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Calculate available stock (on_hand minus reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    /**
     * Check if stock is below reorder point.
     */
    public function isLowStock(): bool
    {
        return $this->getAvailableQuantityAttribute() <= $this->reorder_point;
    }
}
