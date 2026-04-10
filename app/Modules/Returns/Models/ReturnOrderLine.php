<?php

namespace App\Modules\Returns\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReturnOrderLine extends BaseModel
{
    protected $table = 'return_order_lines';

    protected $fillable = [
        'return_order_id',
        'original_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'location_id',
        'uom_id',
        'quantity',
        'unit_price',
        'condition',
        'restock_action',
        'quality_check_notes',
        'stock_movement_id',
        'tax_code_id',
        'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'total' => 'decimal:4'
    ];

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Returns\Models\ReturnOrder::class, 'return_order_id');
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

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\TaxCode::class, 'tax_code_id');
    }
}
