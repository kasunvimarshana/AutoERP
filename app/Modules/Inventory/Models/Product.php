<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * Manages products with SKU/variant support.
 * Includes inventory tracking, batch/lot/serial tracking, and multi-attribute support.
 */
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'type',
        'parent_id',
        'barcode',
        'category_id',
        'brand_id',
        'unit_of_measure',
        'cost_price',
        'selling_price',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'is_active',
        'track_inventory',
        'track_serial',
        'track_batch',
        'track_expiry',
        'attributes',
        'images',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'track_serial' => 'boolean',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'attributes' => 'array',
        'images' => 'array',
    ];

    /**
     * Get the tenant that owns the product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Tenant::class);
    }

    /**
     * Get the parent product (for variants).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    /**
     * Get all variants of this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get the brand.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get all stock ledger entries.
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if product is a variant.
     */
    public function isVariant(): bool
    {
        return $this->type === 'variant' && $this->parent_id !== null;
    }

    /**
     * Check if product has variants.
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Scope active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope products by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
