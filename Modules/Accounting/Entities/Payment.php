<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\PaymentFactory;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;

/**
 * Payment Entity
 *
 * Represents a payment received from a customer.
 * Can be applied to one or multiple invoices.
 */
class Payment extends Model
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
        'payment_number',
        'customer_id',
        'invoice_id',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'payment_method',
        'reference',
        'notes',
        'bank_account_id',
        'journal_entry_id',
        'status',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Payment method constants.
     */
    public const METHOD_CASH = 'cash';

    public const METHOD_CHECK = 'check';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CREDIT_CARD = 'credit_card';

    public const METHOD_DEBIT_CARD = 'debit_card';

    public const METHOD_ONLINE = 'online';

    public const METHOD_OTHER = 'other';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo('Modules\Sales\Entities\Customer', 'customer_id');
    }

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the bank account.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    /**
     * Get the journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
