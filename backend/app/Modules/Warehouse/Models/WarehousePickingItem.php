<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use App\Modules\Tenant\Models\Location;
use App\Modules\MasterData\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehousePickingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_picking_id',
        'product_id',
        'variant_id',
        'location_id',
        'quantity_required',
        'quantity_picked',
        'unit_id',
        'batch_number',
        'serial_number',
        'lot_number',
        'expiry_date',
        'sequence',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'quantity_picked' => 'decimal:4',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    public function picking(): BelongsTo
    {
        return $this->belongsTo(WarehousePicking::class, 'warehouse_picking_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity_required - $this->quantity_picked);
    }

    public function getPickedPercentageAttribute(): float
    {
        if ($this->quantity_required <= 0) {
            return 0;
        }

        return round(($this->quantity_picked / $this->quantity_required) * 100, 2);
    }

    public function isShort(): bool
    {
        return $this->quantity_picked < $this->quantity_required;
    }
}
