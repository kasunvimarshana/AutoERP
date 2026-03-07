<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'sku',
        'quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
        'unit_cost',
        'metadata',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_point'     => 'integer',
        'reorder_quantity'  => 'integer',
        'unit_cost'         => 'decimal:4',
        'metadata'          => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Computed attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Quantity available for new reservations (total minus already reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Whether stock has fallen at or below the reorder threshold.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->reorder_point;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant($query, int $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeLowStock($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= reorder_point');
    }

    public function scopeForProduct($query, int $productId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInWarehouse($query, int $warehouseId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
