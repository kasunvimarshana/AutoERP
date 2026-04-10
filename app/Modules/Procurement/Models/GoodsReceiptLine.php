<?php

namespace App\Modules\Procurement\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoodsReceiptLine extends BaseModel
{
    protected $table = 'goods_receipt_lines';

    protected $fillable = [
        'goods_receipt_id',
        'po_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'location_id',
        'uom_id',
        'received_qty',
        'unit_cost',
        'total_cost',
        'stock_movement_id'
    ];

    protected $casts = [
        'received_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4'
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Procurement\Models\GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Procurement\Models\PurchaseOrderLine::class, 'po_line_id');
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class, 'location_id');
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
