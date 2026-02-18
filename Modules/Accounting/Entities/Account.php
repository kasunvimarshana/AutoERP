<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\AccountFactory;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;

/**
 * Account Entity
 *
 * Represents an account in the chart of accounts.
 * Supports hierarchical structure, multi-currency, and tenant isolation.
 */
class Account extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'uuid',
        'account_number',
        'name',
        'description',
        'type',
        'sub_type',
        'parent_id',
        'currency',
        'is_active',
        'is_system',
        'balance',
        'allow_manual_entries',
        'tax_rate_id',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'allow_manual_entries' => 'boolean',
        'balance' => 'decimal:2',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['name', 'description'];

    /**
     * Account types.
     */
    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    /**
     * Get the parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get journal entry lines for this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get invoice lines using this account.
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if account is a system account.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if account allows manual journal entries.
     */
    public function allowsManualEntries(): bool
    {
        return $this->allow_manual_entries;
    }

    /**
     * Check if account is a debit account.
     */
    public function isDebitAccount(): bool
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE]);
    }

    /**
     * Check if account is a credit account.
     */
    public function isCreditAccount(): bool
    {
        return in_array($this->type, [self::TYPE_LIABILITY, self::TYPE_EQUITY, self::TYPE_REVENUE]);
    }

    /**
     * Update account balance.
     */
    public function updateBalance(float $amount, bool $isDebit): void
    {
        if ($this->isDebitAccount()) {
            $this->balance += $isDebit ? $amount : -$amount;
        } else {
            $this->balance += $isDebit ? -$amount : $amount;
        }
        $this->save();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
