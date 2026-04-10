<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeliveryLine extends BaseModel
{
    protected $table = 'delivery_lines';

    protected $fillable = [
        'delivery_order_id',
        'so_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'uom_id',
        'delivered_qty',
        'stock_movement_id'
    ];

    protected $casts = [
        'delivered_qty' => 'decimal:4'
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\DeliveryOrder::class, 'delivery_order_id');
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\SalesOrderLine::class, 'so_line_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\Batch::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\SerialNumber::class, 'serial_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\StockMovement::class, 'stock_movement_id');
    }
}
