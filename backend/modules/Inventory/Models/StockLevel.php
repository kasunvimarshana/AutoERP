<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Stock Level Model
 *
 * Represents current stock levels per product per location.
 * This is a materialized/denormalized view of the stock ledger for performance.
 */
class StockLevel extends BaseModel
{
    use HasFactory;

    protected $table = 'stock_levels';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'location_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_allocated',
        'quantity_damaged',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:4',
        'quantity_reserved' => 'decimal:4',
        'quantity_allocated' => 'decimal:4',
        'quantity_damaged' => 'decimal:4',
        'last_movement_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the total quantity on hand.
     */
    public function getQuantityOnHandAttribute(): float
    {
        return $this->quantity_available + $this->quantity_reserved + $this->quantity_allocated;
    }
}
