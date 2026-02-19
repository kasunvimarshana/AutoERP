<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\CRM\Models\Customer;
use Modules\Sales\Enums\OrderStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Order Model
 *
 * Represents a confirmed sales order from a customer.
 * Can be created directly or converted from an accepted quotation.
 */
class Order extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'customer_id',
        'quotation_id',
        'order_code',
        'reference',
        'status',
        'order_date',
        'delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'paid_amount',
        'notes',
        'terms_conditions',
        'created_by',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'shipping_cost' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'paid_amount' => 'decimal:6',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the order.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the customer for this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the quotation this order was converted from.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Get the order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the invoices for this order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if order can be modified.
     */
    public function canModify(): bool
    {
        return $this->status->canModify();
    }

    /**
     * Check if order can be confirmed.
     */
    public function canConfirm(): bool
    {
        return $this->status->canConfirm();
    }

    /**
     * Check if order can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Check if order can be completed.
     */
    public function canComplete(): bool
    {
        return $this->status->canComplete();
    }

    /**
     * Check if order is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return bccomp((string) $this->paid_amount, (string) $this->total_amount, 6) >= 0;
    }

    /**
     * Get the remaining unpaid amount.
     */
    public function getRemainingAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 6);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [OrderStatus::DRAFT, OrderStatus::PENDING]);
    }

    /**
     * Scope to get active orders (not cancelled or completed).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::COMPLETED]);
    }
}
