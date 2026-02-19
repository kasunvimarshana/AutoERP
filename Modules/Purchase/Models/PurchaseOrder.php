<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * PurchaseOrder Model
 *
 * Represents a purchase order sent to a vendor for goods or services.
 * Tracks approval, receipt, and billing status.
 */
class PurchaseOrder extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'vendor_id',
        'po_code',
        'reference',
        'status',
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'terms_conditions',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'confirmed_at',
        'received_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'shipping_cost' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the purchase order.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the vendor for this purchase order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the purchase order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the goods receipts for this purchase order.
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * Get the bills for this purchase order.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if purchase order can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if purchase order can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->isCancellable();
    }

    /**
     * Check if purchase order can receive goods.
     */
    public function canReceiveGoods(): bool
    {
        return $this->status->canReceiveGoods();
    }

    /**
     * Check if purchase order is fully received.
     */
    public function isFullyReceived(): bool
    {
        $totalOrdered = $this->items()->sum('quantity');
        $totalReceived = $this->items()->sum('quantity_received');

        return bccomp((string) $totalReceived, (string) $totalOrdered, 6) >= 0;
    }

    /**
     * Check if purchase order is fully billed.
     */
    public function isFullyBilled(): bool
    {
        $totalOrdered = $this->items()->sum('quantity');
        $totalBilled = $this->items()->sum('quantity_billed');

        return bccomp((string) $totalBilled, (string) $totalOrdered, 6) >= 0;
    }
}
