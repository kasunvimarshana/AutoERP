<?php

namespace App\Modules\POS\Models;

use App\Modules\Inventory\Models\Product;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'line_number',
        'quantity',
        'unit',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'description',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateLineTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->discount_amount = $subtotal * ($this->discount_percentage / 100);
        $taxableAmount = $subtotal - $this->discount_amount;
        $this->tax_amount = $taxableAmount * ($this->tax_percentage / 100);
        $this->line_total = $taxableAmount + $this->tax_amount;
    }
}
