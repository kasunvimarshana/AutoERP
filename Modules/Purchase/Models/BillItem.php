<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * BillItem Model
 *
 * Line items for vendor bills with product, quantity, pricing.
 */
class BillItem extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'bill_id',
        'purchase_order_item_id',
        'goods_receipt_item_id',
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
     * Get the bill that owns the item.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the purchase order item this bill item was created from.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Get the goods receipt item this bill item was created from.
     */
    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
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

    /**
     * Calculate line total using BCMath.
     */
    public function calculateTotal(): string
    {
        $subtotal = bcmul((string) $this->quantity, (string) $this->unit_price, 6);
        $subtotal = bcsub($subtotal, (string) $this->discount_amount, 6);
        $total = bcadd($subtotal, (string) $this->tax_amount, 6);

        return $total;
    }

    /**
     * Calculate subtotal using BCMath.
     */
    public function calculateSubtotal(): string
    {
        $subtotal = bcmul((string) $this->quantity, (string) $this->unit_price, 6);
        $subtotal = bcsub($subtotal, (string) $this->discount_amount, 6);

        return $subtotal;
    }
}
