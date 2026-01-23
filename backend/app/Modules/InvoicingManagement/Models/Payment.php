<?php

namespace App\Modules\InvoicingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'invoice_id',
        'customer_id',
        'payment_method',
        'amount',
        'payment_date',
        'reference_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = Str::uuid();
            }
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    /**
     * Generate unique payment number
     */
    protected static function generatePaymentNumber(): string
    {
        do {
            $code = 'PAY-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('payment_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the payment
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the invoice that owns the payment
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that owns the payment
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CustomerManagement\Models\Customer::class);
    }

    /**
     * Get the user who received the payment
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By payment method
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: By date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope: For customer
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: For invoice
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }
}
