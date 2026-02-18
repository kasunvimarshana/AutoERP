<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\InvoiceFactory;
use Modules\Accounting\Enums\InvoiceStatusEnum;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;

/**
 * Invoice Entity
 *
 * Represents a customer invoice.
 * Supports multi-currency, payment tracking, and aging.
 */
class Invoice extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'uuid',
        'invoice_number',
        'customer_id',
        'invoice_date',
        'due_date',
        'payment_terms',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'amount_due',
        'status',
        'notes',
        'terms_conditions',
        'reference',
        'journal_entry_id',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'status' => InvoiceStatusEnum::class,
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants (DEPRECATED - use InvoiceStatusEnum instead)
     *
     * @deprecated Use InvoiceStatusEnum enum cases instead
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get invoice lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo('Modules\Sales\Entities\Customer', 'customer_id');
    }

    /**
     * Get the journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE ||
            ($this->due_date < now() && $this->amount_due > 0);
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Calculate days overdue.
     */
    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Calculate and update totals from lines.
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->lines()->sum('subtotal');
        $this->tax_amount = $this->lines()->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->amount_due = $this->total_amount - $this->amount_paid;
        $this->save();
    }

    /**
     * Apply payment to invoice.
     */
    public function applyPayment(float $amount): void
    {
        $this->amount_paid += $amount;
        $this->amount_due = $this->total_amount - $this->amount_paid;

        if ($this->amount_due <= 0) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }

        $this->save();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
