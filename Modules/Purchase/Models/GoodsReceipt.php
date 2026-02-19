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
use Modules\Purchase\Enums\GoodsReceiptStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * GoodsReceipt Model
 *
 * Represents a goods receipt note when items are received from a vendor.
 * Tracks acceptance, rejection, and inventory posting status.
 */
class GoodsReceipt extends Model
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
        'receipt_code',
        'reference',
        'status',
        'receipt_date',
        'delivery_note',
        'received_by',
        'notes',
        'created_by',
        'confirmed_at',
        'posted_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => GoodsReceiptStatus::class,
        'receipt_date' => 'date',
        'confirmed_at' => 'datetime',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the goods receipt.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the vendor for this goods receipt.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the purchase order for this goods receipt.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the goods receipt items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * Get the bills for this goods receipt.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if goods receipt can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if goods receipt can be posted to inventory.
     */
    public function canPostToInventory(): bool
    {
        return $this->status->canPostToInventory();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, GoodsReceiptStatus $status)
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
     * Scope to get unposted receipts.
     */
    public function scopeUnposted($query)
    {
        return $query->where('status', '!=', GoodsReceiptStatus::POSTED)
            ->where('status', '!=', GoodsReceiptStatus::CANCELLED);
    }
}
