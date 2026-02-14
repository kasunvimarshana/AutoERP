<?php

namespace App\Modules\Invoice\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Invoice",
 *     type="object",
 *     title="Invoice",
 *     description="Invoice model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="customer_id", type="string", format="uuid"),
 *     @OA\Property(property="order_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="invoice_number", type="string", example="INV-2024-001"),
 *     @OA\Property(property="invoice_date", type="string", format="date"),
 *     @OA\Property(property="due_date", type="string", format="date"),
 *     @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "overdue", "cancelled"}),
 *     @OA\Property(property="subtotal", type="number", format="float", example=1000.00),
 *     @OA\Property(property="tax_amount", type="number", format="float", example=150.00),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=50.00),
 *     @OA\Property(property="total_amount", type="number", format="float", example=1100.00),
 *     @OA\Property(property="paid_amount", type="number", format="float", example=500.00),
 *     @OA\Property(property="balance_due", type="number", format="float", example=600.00),
 *     @OA\Property(property="payment_status", type="string", enum={"unpaid", "partial", "paid"}),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Invoice extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'payment_status',
        'notes',
        'settings',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    public function canBeSent(): bool
    {
        return $this->status === 'draft';
    }

    public function canReceivePayment(): bool
    {
        return in_array($this->status, ['sent', 'overdue']) && !$this->isFullyPaid();
    }

    public function updatePaymentStatus(): void
    {
        if ($this->paid_amount <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = 'paid';
            $this->status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }
        
        $this->balance_due = $this->total_amount - $this->paid_amount;
    }

    public function checkOverdue(): void
    {
        if ($this->due_date < now() && !$this->isFullyPaid() && !$this->isCancelled() && !$this->isDraft()) {
            $this->status = 'overdue';
        }
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, string $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
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

    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('invoice_date', [$from, $to]);
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('invoice_number', 'like', "%{$searchTerm}%")
              ->orWhereHas('customer', function ($query) use ($searchTerm) {
                  $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
              });
        });
    }
}
