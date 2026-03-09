<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'attributes',
        'images',
        'unit_price',
        'cost_price',
        'unit_of_measure',
        'weight',
        'dimensions',
        'barcode',
        'minimum_stock',
        'reorder_point',
        'reorder_quantity',
        'is_active',
    ];

    protected $casts = [
        'attributes'       => 'array',
        'images'           => 'array',
        'dimensions'       => 'array',
        'unit_price'       => 'decimal:4',
        'cost_price'       => 'decimal:4',
        'weight'           => 'decimal:4',
        'minimum_stock'    => 'integer',
        'reorder_point'    => 'integer',
        'reorder_quantity' => 'integer',
        'is_active'        => 'boolean',
        'deleted_at'       => 'datetime',
    ];

    protected $attributes = [
        'minimum_stock'    => 0,
        'reorder_point'    => 0,
        'reorder_quantity' => 0,
        'is_active'        => true,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeBySku($query, string $sku)
    {
        return $query->whereRaw('LOWER(sku) = ?', [strtolower($sku)]);
    }

    public function scopeLowStock($query, ?int $threshold = null)
    {
        return $query->whereHas('stockLevels', function ($q) use ($threshold) {
            if ($threshold !== null) {
                $q->where('quantity_available', '<=', $threshold);
            } else {
                $q->whereRaw('quantity_available <= products.reorder_point');
            }
        });
    }

    public function scopeWithStockSummary($query)
    {
        return $query->with('stockLevels');
    }

    // -------------------------------------------------------------------------
    // Accessors & Helpers
    // -------------------------------------------------------------------------

    public function getTotalAvailableAttribute(): int
    {
        return (int) $this->stockLevels->sum('quantity_available');
    }

    public function isLowStock(?int $threshold = null): bool
    {
        $available = $this->stockLevels->sum('quantity_available');
        $threshold = $threshold ?? $this->reorder_point;
        return $available <= $threshold;
    }
}
