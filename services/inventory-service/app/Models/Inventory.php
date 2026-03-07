<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inventories';

    protected $fillable = [
        'product_id',
        'quantity',
        'reserved_quantity',
        'warehouse_location',
        'reorder_level',
        'reorder_quantity',
        'unit_cost',
        'status',
        'last_counted_at',
    ];

    protected $casts = [
        'product_id'         => 'integer',
        'quantity'           => 'integer',
        'reserved_quantity'  => 'integer',
        'reorder_level'      => 'integer',
        'reorder_quantity'   => 'integer',
        'unit_cost'          => 'decimal:4',
        'last_counted_at'    => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouseLocation(Builder $query, string $location): Builder
    {
        return $query->where('warehouse_location', 'like', "%{$location}%");
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level')
                     ->where('quantity', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', 0);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('warehouse_location', 'like', "%{$term}%")
              ->orWhere('product_id', 'like', "%{$term}%");
        });
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->reorder_level;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity <= 0;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
