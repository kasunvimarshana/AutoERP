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

class WarehousePutawayItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_putaway_id',
        'product_id',
        'variant_id',
        'destination_location_id',
        'quantity_to_putaway',
        'quantity_putaway',
        'unit_id',
        'batch_number',
        'serial_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'sequence',
        'status',
        'unit_cost',
        'total_cost',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity_to_putaway' => 'decimal:4',
        'quantity_putaway' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    public function putaway(): BelongsTo
    {
        return $this->belongsTo(WarehousePutaway::class, 'warehouse_putaway_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity_to_putaway - $this->quantity_putaway);
    }

    public function getPutawayPercentageAttribute(): float
    {
        if ($this->quantity_to_putaway <= 0) {
            return 0;
        }

        return round(($this->quantity_putaway / $this->quantity_to_putaway) * 100, 2);
    }
}
