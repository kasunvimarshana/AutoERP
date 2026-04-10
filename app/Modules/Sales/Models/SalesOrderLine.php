<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesOrderLine extends BaseModel
{
    protected $table = 'sales_order_lines';

    protected $fillable = [
        'sales_order_id',
        'line_number',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'ordered_qty',
        'shipped_qty',
        'invoiced_qty',
        'unit_price',
        'discount_pct',
        'tax_code_id',
        'subtotal',
        'tax_amount',
        'total'
    ];

    protected $casts = [
        'line_number' => 'integer',
        'ordered_qty' => 'decimal:4',
        'shipped_qty' => 'decimal:4',
        'invoiced_qty' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'total' => 'decimal:4'
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\TaxCode::class, 'tax_code_id');
    }
}
