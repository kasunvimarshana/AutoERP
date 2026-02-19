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
use Modules\Purchase\Enums\VendorStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * Vendor Model
 *
 * Represents a vendor/supplier from whom goods or services are purchased.
 * Manages vendor master data, credit limits, and payment terms.
 */
class Vendor extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'vendor_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'website',
        'tax_id',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'payment_terms_days',
        'credit_limit',
        'current_balance',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'status' => VendorStatus::class,
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:6',
        'current_balance' => 'decimal:6',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the vendor.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the purchase orders for this vendor.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the bills for this vendor.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get the payments made to this vendor.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /**
     * Check if vendor can receive new orders.
     */
    public function canReceiveOrders(): bool
    {
        return $this->status->canReceiveOrders();
    }

    /**
     * Check if vendor has exceeded credit limit.
     */
    public function hasExceededCreditLimit(): bool
    {
        if ($this->credit_limit === null) {
            return false;
        }

        return bccomp($this->current_balance, $this->credit_limit, 6) > 0;
    }

    /**
     * Get available credit.
     */
    public function availableCredit(): string
    {
        if ($this->credit_limit === null) {
            return '0.000000';
        }

        $available = bcsub($this->credit_limit, $this->current_balance, 6);

        return bccomp($available, '0', 6) > 0 ? $available : '0.000000';
    }
}
