<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Sales\Enums\PaymentMethod;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

/**
 * BillPayment Model
 *
 * Records payments made to vendors for bills.
 * Tracks payment method, amount, and reconciliation status.
 */
class BillPayment extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'bill_id',
        'vendor_id',
        'payment_code',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_id',
        'reference_number',
        'notes',
        'reconciled',
        'reconciled_at',
        'reconciled_by',
        'recorded_by',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:6',
        'payment_method' => PaymentMethod::class,
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Get the organization that made the payment.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the bill this payment is for.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the vendor for this payment.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Check if payment is reconciled.
     */
    public function isReconciled(): bool
    {
        return $this->reconciled === true;
    }

    /**
     * Scope to filter by payment method.
     */
    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to get unreconciled payments.
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('reconciled', false);
    }

    /**
     * Scope to get reconciled payments.
     */
    public function scopeReconciled($query)
    {
        return $query->where('reconciled', true);
    }

    /**
     * Scope to filter by vendor.
     */
    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
