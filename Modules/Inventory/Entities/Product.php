<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Inventory\Database\Factories\ProductFactory;

/**
 * Product Entity
 *
 * Represents a product in the inventory system with support for
 * multi-language descriptions, pricing, and inventory tracking.
 */
class Product extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'product_category_id',
        'unit_of_measure_id',
        'sku',
        'barcode',
        'name',
        'description',
        'product_type',
        'status',
        'list_price',
        'cost_price',
        'currency',
        'weight',
        'length',
        'width',
        'height',
        'dimension_unit',
        'weight_unit',
        'reorder_level',
        'reorder_quantity',
        'lead_time_days',
        'shelf_life_days',
        'is_serialized',
        'is_batch_tracked',
        'image_url',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'list_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'lead_time_days' => 'integer',
        'shelf_life_days' => 'integer',
        'is_serialized' => 'boolean',
        'is_batch_tracked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['name', 'description', 'notes'];

    /**
     * Get the product category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the unit of measure.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }

    /**
     * Get stock levels for this product.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if product is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if product can be sold.
     */
    public function isSellable(): bool
    {
        return in_array($this->product_type, ['stockable', 'consumable']);
    }

    /**
     * Check if product is stockable (tracks inventory).
     */
    public function isStockable(): bool
    {
        return $this->product_type === 'stockable';
    }

    /**
     * Get total available quantity across all warehouses.
     */
    public function getTotalAvailableQuantity(): float
    {
        return $this->stockLevels()
            ->sum('quantity_available');
    }

    /**
     * Get available quantity in a specific warehouse.
     */
    public function getAvailableQuantityInWarehouse(int $warehouseId): float
    {
        return $this->stockLevels()
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity_available');
    }

    /**
     * Check if product needs reordering.
     */
    public function needsReorder(): bool
    {
        if (! $this->reorder_level) {
            return false;
        }

        return $this->getTotalAvailableQuantity() <= $this->reorder_level;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
