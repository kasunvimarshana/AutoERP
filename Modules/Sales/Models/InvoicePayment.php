<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Sales\Enums\PaymentMethod;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * InvoicePayment Model
 *
 * Records payments made against sales invoices.
 * Tracks payment method, amount, and reconciliation status.
 */
class InvoicePayment extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'payment_code',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'reconciled',
        'reconciled_at',
        'reconciled_by',
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
     * Get the invoice this payment is for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
}
