<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Product extends BaseModel
{
    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'brand_id',
        'sku',
        'name',
        'description',
        'type',
        'base_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'track_inventory',
        'track_batch',
        'track_serial',
        'has_expiry',
        'min_stock_level',
        'reorder_point',
        'valuation_method',
        'inventory_account_id',
        'cogs_account_id',
        'income_account_id',
        'is_active',
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'has_expiry' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Brand::class, 'brand_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'base_uom_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'purchase_uom_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'sales_uom_id');
    }
}
