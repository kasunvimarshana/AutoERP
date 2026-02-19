<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\JobCard\Models\JobCard;
use Modules\Organization\Models\Branch;

/**
 * Invoice Model
 *
 * Represents an invoice for vehicle service
 *
 * @property int $id
 * @property int|null $job_card_id
 * @property int $customer_id
 * @property int|null $vehicle_id
 * @property int $branch_id
 * @property string $invoice_number
 * @property \Carbon\Carbon $invoice_date
 * @property \Carbon\Carbon|null $due_date
 * @property float $subtotal
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property float $amount_paid
 * @property float $balance
 * @property string $status
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Invoice extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model . */
    protected static function newFactory(): \Modules\Invoice\Database\Factories\InvoiceFactory
    {
        return \Modules\Invoice\Database\Factories\InvoiceFactory::new();
    }

    /**
     * The attributes that are mass assignable . *
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_card_id',
        'customer_id',
        'vehicle_id',
        'branch_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'status',
        'payment_terms',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast . *
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the job card that owns the invoice . */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Get the customer for the invoice . */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicle for the invoice . */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the branch for the invoice . */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the items for the invoice . */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for the invoice . */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the commissions for the invoice . */
    public function commissions(): HasMany
    {
        return $this->hasMany(DriverCommission::class);
    }

    /**
     * Scope to filter by status . */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by customer . */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by branch . */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter overdue invoices . */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('balance', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled', 'refunded']);
    }

    /**
     * Scope to filter outstanding invoices . */
    public function scopeOutstanding($query)
    {
        return $query->where('balance', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled', 'refunded']);
    }

    /**
     * Generate a unique invoice number . */
    public static function generateInvoiceNumber(): string
    {
        do {
            $prefix = 'INV';
            $timestamp = now()->format('Ymd');
            // Use random_int for better randomness and add microseconds for more entropy
            $random = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $microtime = str_pad((string) (int) (microtime(true) * 10000 % 10000), 4, '0', STR_PAD_LEFT);

            $invoiceNumber = "{$prefix}-{$timestamp}-{$random}{$microtime}";

            // Check if the number already exists
            $exists = static::where('invoice_number', $invoiceNumber)->exists();
        } while ($exists);

        return $invoiceNumber;
    }

    /**
     * Check if invoice is overdue . */
    public function isOverdue(): bool
    {
        if (! $this->due_date || $this->balance <= 0) {
            return false;
        }

        return $this->due_date->isPast() && ! in_array($this->status, ['paid', 'cancelled', 'refunded']);
    }

    /**
     * Check if invoice is fully paid . */
    public function isPaid(): bool
    {
        return $this->balance <= 0 || $this->status === 'paid';
    }
}
