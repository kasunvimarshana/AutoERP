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
 * GoodsReceiptItem Model
 *
 * Line items for goods receipts with acceptance and rejection tracking.
 */
class GoodsReceiptItem extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'unit_id',
        'rejection_reason',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:6',
        'quantity_received' => 'decimal:6',
        'quantity_accepted' => 'decimal:6',
        'quantity_rejected' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    /**
     * Get the goods receipt that owns the item.
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * Get the purchase order item this receipt item is for.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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
     * Get the variance between ordered and received quantities.
     */
    public function getQuantityVariance(): string
    {
        return bcsub((string) $this->quantity_received, (string) $this->quantity_ordered, 6);
    }

    /**
     * Check if there are rejected items.
     */
    public function hasRejections(): bool
    {
        return bccomp((string) $this->quantity_rejected, '0', 6) > 0;
    }

    /**
     * Check if all received items were accepted.
     */
    public function isFullyAccepted(): bool
    {
        return bccomp((string) $this->quantity_accepted, (string) $this->quantity_received, 6) >= 0;
    }
}
