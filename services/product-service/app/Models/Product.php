<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'cost_price',
        'unit',
        'weight',
        'dimensions',
        'images',
        'attributes',
        'is_active',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
    ];

    protected $casts = [
        'price'           => 'decimal:4',
        'cost_price'      => 'decimal:4',
        'weight'          => 'decimal:4',
        'dimensions'      => 'array',
        'images'          => 'array',
        'attributes'      => 'array',
        'is_active'       => 'boolean',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The tenant this product belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * The category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, int|string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Products that are at or below their reorder threshold.
     */
    public function scopeLowStock(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('reorder_point')
                     ->whereNotNull('min_stock_level')
                     ->whereColumn('min_stock_level', '<=', 'reorder_point');
    }
}
