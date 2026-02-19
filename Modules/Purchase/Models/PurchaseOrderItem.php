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
 * PurchaseOrderItem Model
 *
 * Line items for purchase orders with product, quantity, pricing.
 */
class PurchaseOrderItem extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
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
        'quantity_received',
        'quantity_billed',
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
        'quantity_received' => 'decimal:6',
        'quantity_billed' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    /**
     * Get the purchase order that owns the item.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
     * Get the remaining quantity to receive.
     */
    public function getRemainingToReceive(): string
    {
        return bcsub((string) $this->quantity, (string) $this->quantity_received, 6);
    }

    /**
     * Get the remaining quantity to bill.
     */
    public function getRemainingToBill(): string
    {
        return bcsub((string) $this->quantity, (string) $this->quantity_billed, 6);
    }

    /**
     * Check if item is fully received.
     */
    public function isFullyReceived(): bool
    {
        return bccomp((string) $this->quantity_received, (string) $this->quantity, 6) >= 0;
    }

    /**
     * Check if item is fully billed.
     */
    public function isFullyBilled(): bool
    {
        return bccomp((string) $this->quantity_billed, (string) $this->quantity, 6) >= 0;
    }
}
