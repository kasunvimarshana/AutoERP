<?php

namespace App\Modules\InvoicingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'job_card_id',
        'customer_id',
        'vehicle_id',
        'status',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'terms_and_conditions',
        'notes',
        'sent_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = Str::uuid();
            }
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate unique invoice number
     */
    protected static function generateInvoiceNumber(): string
    {
        do {
            $code = 'INV-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('invoice_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the invoice
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the job card associated with the invoice
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\JobCardManagement\Models\JobCard::class);
    }

    /**
     * Get the customer that owns the invoice
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CustomerManagement\Models\Customer::class);
    }

    /**
     * Get the vehicle associated with the invoice
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\VehicleManagement\Models\Vehicle::class);
    }

    /**
     * Get the user who created the invoice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the invoice items for the invoice
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for the invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if invoice is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue' || 
               ($this->due_date < now() && !in_array($this->status, ['paid', 'cancelled']));
    }

    /**
     * Check if invoice is partially paid
     */
    public function getIsPartiallyPaidAttribute(): bool
    {
        return $this->status === 'partially_paid' || 
               ($this->paid_amount > 0 && $this->paid_amount < $this->total_amount);
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
     * Scope: Active invoices
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * Scope: Paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', ['paid', 'cancelled']);
    }
}
