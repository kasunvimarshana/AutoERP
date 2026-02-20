<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single cost layer used for FIFO/FEFO inventory valuation.
 *
 * Each inbound stock movement (receipt, adjustment-in, purchase receipt) creates
 * one StockBatch row.  When stock is consumed (shipment, adjustment-out) the
 * oldest batches (FIFO) or those nearest expiry (FEFO) are depleted first and
 * `quantity_remaining` is decremented accordingly.
 */
class StockBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'product_id', 'variant_id', 'movement_id',
        'batch_number', 'lot_number', 'serial_number', 'expiry_date',
        'quantity_received', 'quantity_remaining', 'cost_per_unit', 'currency',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'string',
            'quantity_remaining' => 'string',
            'cost_per_unit' => 'string',
            'expiry_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }
}
