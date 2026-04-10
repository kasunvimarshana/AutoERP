<?php

namespace App\Modules\Procurement\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseOrderLine extends BaseModel
{
    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'purchase_order_id',
        'line_number',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'ordered_qty',
        'received_qty',
        'billed_qty',
        'unit_price',
        'discount_pct',
        'tax_code_id',
        'subtotal',
        'tax_amount',
        'total',
        'account_id'
    ];

    protected $casts = [
        'line_number' => 'integer',
        'ordered_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'billed_qty' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'total' => 'decimal:4'
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Procurement\Models\PurchaseOrder::class, 'purchase_order_id');
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
