<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * InvoiceItem Model
 *
 * Line items for sales invoices with product, quantity, pricing.
 */
class InvoiceItem extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'order_item_id',
        'product_id',
        'description',
        'quantity',
        'unit_id',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:6',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'total' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the order item this invoice item was created from.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit for this item.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
