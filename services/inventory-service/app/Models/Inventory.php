<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'unit_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'unit_cost'         => 'decimal:4',
        'last_movement_at'  => 'datetime',
    ];

    protected $appends = ['available_quantity'];

    // -------------------------------------------------------------------------
    // Computed attributes
    // -------------------------------------------------------------------------

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to a single tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Items whose available quantity is at or below the low-stock threshold.
     */
    public function scopeLowStock($query, ?int $threshold = null)
    {
        $limit = $threshold ?? (int) config('tenant.low_stock_threshold', 10);

        return $query->whereRaw('(quantity - reserved_quantity) <= ?', [$limit])
                     ->where('quantity', '>', 0);
    }

    /**
     * Items that have been fully depleted (available = 0).
     */
    public function scopeDepleted($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0');
    }

    /**
     * Items belonging to a specific warehouse.
     */
    public function scopeInWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Items for a specific product.
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }
}
