<?php

declare(strict_types=1);

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\CRM\Models\Customer;
use App\Modules\Procurement\Models\Vendor;
use App\Modules\Invoice\Models\Invoice;

class Payment extends Model
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
        'payment_number',
        'payment_date',
        'payment_type',
        'entity_type',
        'entity_id',
        'payment_method_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'base_amount',
        'reference_number',
        'transaction_id',
        'bank_name',
        'account_number',
        'cheque_number',
        'cheque_date',
        'card_last_four',
        'card_type',
        'notes',
        'status',
        'reconciliation_status',
        'reconciled_at',
        'reconciled_by',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the payment method
     *
     * @return BelongsTo
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the payment allocations
     *
     * @return HasMany
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the entity (Customer or Vendor) - polymorphic
     *
     * @return BelongsTo
     */
    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the reconciler
     *
     * @return BelongsTo
     */
    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Scope for pending payments
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed payments
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for unreconciled payments
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('reconciliation_status', 'unreconciled');
    }

    /**
     * Check if payment is fully allocated
     *
     * @return bool
     */
    public function isFullyAllocated(): bool
    {
        $allocatedAmount = $this->allocations()->sum('amount');
        return bccomp((string)$allocatedAmount, (string)$this->amount, 2) === 0;
    }

    /**
     * Get remaining amount to allocate
     *
     * @return string
     */
    public function getRemainingAmount(): string
    {
        $allocatedAmount = $this->allocations()->sum('amount');
        return bcsub((string)$this->amount, (string)$allocatedAmount, 2);
    }
}
