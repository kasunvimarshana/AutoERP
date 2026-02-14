<?php

namespace App\Modules\Payment\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Payment model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="customer_id", type="string", format="uuid"),
 *     @OA\Property(property="invoice_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="payment_number", type="string", example="PAY-2026-001"),
 *     @OA\Property(property="payment_date", type="string", format="date"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash", "card", "bank_transfer", "cheque", "online", "other"}),
 *     @OA\Property(property="amount", type="number", format="float", example=1000.00),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}),
 *     @OA\Property(property="reference_number", type="string"),
 *     @OA\Property(property="transaction_id", type="string"),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Payment extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'invoice_id',
        'payment_number',
        'payment_date',
        'payment_method',
        'amount',
        'currency',
        'status',
        'reference_number',
        'transaction_id',
        'notes',
        'settings',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'payment_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function canBeRefunded(): bool
    {
        return $this->status === 'completed';
    }

    public function getTotalRefunded(): float
    {
        return (float) $this->refunds()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getRemainingAmount(): float
    {
        return (float) ($this->amount - $this->getTotalRefunded());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByInvoice($query, string $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('payment_number', 'like', "%{$searchTerm}%")
              ->orWhere('reference_number', 'like', "%{$searchTerm}%")
              ->orWhere('transaction_id', 'like', "%{$searchTerm}%")
              ->orWhereHas('customer', function ($query) use ($searchTerm) {
                  $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
              });
        });
    }
}
