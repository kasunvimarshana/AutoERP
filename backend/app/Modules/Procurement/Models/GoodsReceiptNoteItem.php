<?php

namespace App\Modules\Procurement\Models;

use App\Modules\Inventory\Models\Product;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Goods Receipt Note Item Model
 *
 * Represents individual line items in a goods receipt note
 */
class GoodsReceiptNoteItem extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'goods_receipt_note_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_rejected',
        'unit_price',
        'batch_number',
        'serial_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the goods receipt note that owns this item
     */
    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class);
    }

    /**
     * Get the product associated with this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the purchase order item associated with this item
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Check if this item has a discrepancy
     */
    public function hasDiscrepancy(): bool
    {
        return $this->quantity_received !== $this->quantity_ordered;
    }

    /**
     * Calculate the discrepancy quantity
     */
    public function discrepancyQuantity(): int
    {
        return $this->quantity_ordered - $this->quantity_received;
    }
}
