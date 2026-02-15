<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Modules\Tenant\Models\Location;
use App\Modules\MasterData\Models\Currency;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'organization_id',
        'branch_id',
        'location_id',
        'vendor_id',
        'code',
        'reference_number',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'approval_status',
        'payment_status',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'discount_type',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'shipping_amount',
        'other_charges',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_terms_days',
        'payment_method',
        'shipping_method',
        'shipping_address',
        'billing_address',
        'notes',
        'internal_notes',
        'terms_conditions',
        'tags',
        'custom_fields',
        'metadata',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'tags' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the vendor that owns the purchase order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the organization that owns the purchase order.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the branch that owns the purchase order.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the location for the purchase order.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the currency for the purchase order.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the items for the purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the receipts for the purchase order.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    /**
     * Get the user who approved the purchase order.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who cancelled the purchase order.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the user who created the purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the purchase order.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if purchase order is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if purchase order is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if purchase order is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if purchase order is rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if purchase order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if purchase order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if purchase order is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->items->every(function ($item) {
            return $item->received_quantity >= $item->quantity;
        });
    }

    /**
     * Check if purchase order is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->items->some(function ($item) {
            return $item->received_quantity > 0 && $item->received_quantity < $item->quantity;
        });
    }

    /**
     * Check if purchase order is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid' && $this->balance_amount <= 0;
    }

    /**
     * Check if purchase order is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial' && $this->paid_amount > 0 && $this->balance_amount > 0;
    }

    /**
     * Calculate received percentage.
     */
    public function getReceivedPercentage(): float
    {
        $totalQuantity = $this->items->sum('quantity');
        $receivedQuantity = $this->items->sum('received_quantity');

        if ($totalQuantity <= 0) {
            return 0;
        }

        return round(($receivedQuantity / $totalQuantity) * 100, 2);
    }

    /**
     * Calculate payment percentage.
     */
    public function getPaymentPercentage(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }
}
