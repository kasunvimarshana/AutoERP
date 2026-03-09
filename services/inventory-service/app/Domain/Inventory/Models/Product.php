<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * Represents a sellable product in the inventory system.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property float $price
 * @property float $cost
 * @property string $unit
 * @property bool $is_active
 * @property array|null $attributes
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'category',
        'price',
        'cost',
        'unit',
        'weight',
        'dimensions',
        'is_active',
        'attributes',
        'image_url',
    ];

    protected $casts = [
        'price' => 'float',
        'cost' => 'float',
        'weight' => 'float',
        'dimensions' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get inventory items for this product across all warehouses.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'product_id');
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Get total available stock across all warehouses.
     */
    public function getTotalAvailableStockAttribute(): int
    {
        return $this->inventoryItems->sum(fn ($item) => max(0, $item->quantity_on_hand - $item->quantity_reserved));
    }
}
