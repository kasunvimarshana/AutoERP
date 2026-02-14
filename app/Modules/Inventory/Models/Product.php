<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * Represents products/items in the inventory system
 */
class Product extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'sku',
        'barcode',
        'name',
        'description',
        'category',
        'brand',
        'unit',
        'cost_price',
        'selling_price',
        'retail_price',
        'wholesale_price',
        'tax_rate',
        'reorder_level',
        'reorder_quantity',
        'is_active',
        'is_tracked',
        'metadata',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_tracked' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the product
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get stock records for this product
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get stock movements for this product
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get total stock quantity across all warehouses
     */
    public function getTotalStock(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Get available stock (quantity - reserved)
     */
    public function getAvailableStock(): int
    {
        return $this->stocks()->sum('quantity') - $this->stocks()->sum('reserved_quantity');
    }
}
