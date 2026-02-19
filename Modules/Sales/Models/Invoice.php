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
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Invoice Model
 *
 * Represents a sales invoice for goods/services provided to customers.
 * Tracks payment status and due dates.
 */
class Invoice extends Model
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
        'order_id',
        'invoice_code',
        'reference',
        'status',
        'invoice_date',
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
        'status' => InvoiceStatus::class,
        'invoice_date' => 'date',
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
     * Get the organization that owns the invoice.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the customer for this invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order this invoice was created from.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === InvoiceStatus::OVERDUE) {
            return true;
        }

        if ($this->due_date && $this->due_date->isPast() && ! $this->isFullyPaid()) {
            return true;
        }

        return false;
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return bccomp((string) $this->paid_amount, (string) $this->total_amount, 6) >= 0;
    }

    /**
     * Check if invoice is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        $paid = (string) $this->paid_amount;

        return bccomp($paid, '0', 6) > 0 && bccomp($paid, (string) $this->total_amount, 6) < 0;
    }

    /**
     * Get the remaining unpaid amount.
     */
    public function getRemainingAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 6);
    }

    /**
     * Check if invoice can be modified.
     */
    public function canModify(): bool
    {
        return $this->status->canModify();
    }

    /**
     * Check if invoice can be sent.
     */
    public function canSend(): bool
    {
        return $this->status->canSend();
    }

    /**
     * Check if invoice can accept payments.
     */
    public function canAcceptPayment(): bool
    {
        return ! $this->isFullyPaid() && $this->status !== InvoiceStatus::CANCELLED;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, InvoiceStatus $status)
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
     * Scope to get unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', InvoiceStatus::PAID)
            ->where('status', '!=', InvoiceStatus::CANCELLED);
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', InvoiceStatus::PAID)
            ->where('status', '!=', InvoiceStatus::CANCELLED)
            ->where('due_date', '<', now());
    }
}
