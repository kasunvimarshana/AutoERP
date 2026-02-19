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
use Modules\Sales\Enums\QuotationStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Quotation Model
 *
 * Represents a sales quotation/quote sent to customers.
 * Can be converted to an Order upon acceptance.
 */
class Quotation extends Model
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
        'quotation_code',
        'reference',
        'status',
        'quotation_date',
        'valid_until',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'terms_conditions',
        'created_by',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'expired_at',
        'converted_at',
        'converted_order_id',
    ];

    protected $casts = [
        'status' => QuotationStatus::class,
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the quotation.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the customer for this quotation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the quotation items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Get the order converted from this quotation.
     */
    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    /**
     * Check if quotation is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === QuotationStatus::EXPIRED) {
            return true;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if quotation can be modified.
     */
    public function canModify(): bool
    {
        return $this->status->canModify();
    }

    /**
     * Check if quotation can be sent.
     */
    public function canSend(): bool
    {
        return $this->status->canSend();
    }

    /**
     * Check if quotation can be converted to order.
     */
    public function canConvert(): bool
    {
        return $this->status->canConvert() && ! $this->isExpired();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, QuotationStatus $status)
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
     * Scope to get expired quotations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', '!=', QuotationStatus::EXPIRED)
            ->where('valid_until', '<', now());
    }
}
