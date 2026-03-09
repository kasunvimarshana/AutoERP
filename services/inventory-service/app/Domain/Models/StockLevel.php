<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_on_hand',
        'version',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:4',
        'quantity_reserved'  => 'decimal:4',
        'quantity_on_hand'   => 'decimal:4',
        'version'            => 'integer',
    ];

    protected $attributes = [
        'quantity_available' => 0,
        'quantity_reserved'  => 0,
        'quantity_on_hand'   => 0,
        'version'            => 1,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function hasAvailableStock(float $qty): bool
    {
        return $this->quantity_available >= $qty;
    }

    public function incrementVersion(): void
    {
        $this->version = ($this->version ?? 1) + 1;
    }
}
