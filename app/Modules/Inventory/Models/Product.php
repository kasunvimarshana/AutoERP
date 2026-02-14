<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product Model
 * 
 * Represents a product in the inventory
 */
class Product extends Model
{
    use HasFactory, TenantScoped, HasUuid, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'brand_id',
        'unit_price',
        'cost_price',
        'unit_of_measure',
        'track_inventory',
        'track_batch',
        'track_serial',
        'track_expiry',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'track_inventory' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'track_expiry' => 'boolean',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    protected static function newFactory()
    {
        return \Database\Factories\ProductFactory::new();
    }

    /**
     * Get the category for this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get the brand for this product
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get stock ledger entries for this product
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }
}
