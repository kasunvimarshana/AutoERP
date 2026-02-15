<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use App\Modules\MasterData\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_transfer_id',
        'product_id',
        'variant_id',
        'quantity_requested',
        'quantity_shipped',
        'quantity_received',
        'unit_id',
        'batch_number',
        'serial_number',
        'lot_number',
        'expiry_date',
        'unit_cost',
        'total_cost',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_shipped' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity_requested - $this->quantity_received);
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity_requested <= 0) {
            return 0;
        }

        return round(($this->quantity_received / $this->quantity_requested) * 100, 2);
    }
}
