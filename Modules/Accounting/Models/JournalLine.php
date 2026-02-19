<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Tenant;

/**
 * Journal Line Model
 *
 * Represents a single line in a journal entry (debit or credit)
 */
class JournalLine extends Model
{
    use HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_id',
        'line_number',
        'description',
        'debit',
        'credit',
        'metadata',
    ];

    protected $casts = [
        'debit' => 'decimal:6',
        'credit' => 'decimal:6',
        'line_number' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Journal entry relationship
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Account relationship
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get line amount (debit or credit)
     */
    public function getAmountAttribute(): string
    {
        return bccomp($this->debit, '0', config('accounting.decimal_scale', 6)) > 0
            ? $this->debit
            : $this->credit;
    }

    /**
     * Check if line is a debit
     */
    public function isDebit(): bool
    {
        return bccomp($this->debit, '0', config('accounting.decimal_scale', 6)) > 0;
    }

    /**
     * Check if line is a credit
     */
    public function isCredit(): bool
    {
        return bccomp($this->credit, '0', config('accounting.decimal_scale', 6)) > 0;
    }
}
