<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerInvoiceLine extends BaseModel
{
    protected $table = 'customer_invoice_lines';

    protected $fillable = [
        'customer_invoice_id',
        'invoice_line_number',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'invoiced_qty',
        'unit_price',
        'discount_pct',
        'tax_code_id',
        'subtotal',
        'tax_amount',
        'total',
        'sales_order_line_id'
    ];

    protected $casts = [
        'invoice_line_number' => 'integer',
        'invoiced_qty' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'total' => 'decimal:4'
    ];

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
