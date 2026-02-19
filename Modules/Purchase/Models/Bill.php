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
use Modules\Purchase\Enums\BillStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Bill Model
 *
 * Represents a vendor bill (invoice) for goods/services received.
 * Tracks payment status and due dates.
 */
class Bill extends Model
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
        'purchase_order_id',
        'goods_receipt_id',
        'bill_code',
        'vendor_invoice_number',
        'reference',
        'status',
        'bill_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'paid_amount',
        'notes',
        'terms_conditions',
        'created_by',
        'sent_at',
        'paid_at',
        'overdue_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => BillStatus::class,
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'shipping_cost' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'paid_amount' => 'decimal:6',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'overdue_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the bill.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the vendor for this bill.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the purchase order this bill was created from.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the goods receipt this bill was created from.
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * Get the bill items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Get the payments for this bill.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /**
     * Check if bill is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === BillStatus::OVERDUE) {
            return true;
        }

        if ($this->due_date && $this->due_date->isPast() && ! $this->isFullyPaid()) {
            return true;
        }

        return false;
    }

    /**
     * Check if bill is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return bccomp((string) $this->paid_amount, (string) $this->total_amount, 6) >= 0;
    }

    /**
     * Check if bill is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        $paid = (string) $this->paid_amount;

        return bccomp($paid, '0', 6) > 0 && bccomp($paid, (string) $this->total_amount, 6) < 0;
    }

    /**
     * Get the remaining unpaid balance.
     */
    public function getRemainingBalance(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 6);
    }

    /**
     * Check if bill can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if bill can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->isCancellable();
    }

    /**
     * Check if bill can accept payments.
     */
    public function canAcceptPayment(): bool
    {
        return ! $this->isFullyPaid() && $this->status !== BillStatus::CANCELLED;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, BillStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by vendor.
     */
    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope to get unpaid bills.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', BillStatus::PAID)
            ->where('status', '!=', BillStatus::CANCELLED);
    }

    /**
     * Scope to get overdue bills.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', BillStatus::PAID)
            ->where('status', '!=', BillStatus::CANCELLED)
            ->where('due_date', '<', now());
    }
}
