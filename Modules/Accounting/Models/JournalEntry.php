<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\JournalEntryStatus;
use Modules\Audit\Contracts\Auditable;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * Journal Entry Model
 *
 * Represents a double-entry bookkeeping journal entry
 */
class JournalEntry extends Model
{
    use AuditableTrait, HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'fiscal_period_id',
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'status',
        'source_type',
        'source_id',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_entry_id',
        'metadata',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'status' => JournalEntryStatus::class,
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
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
     * Organization relationship
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Fiscal period relationship
     */
    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    /**
     * Journal lines relationship
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * Source polymorphic relationship
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Reversal entry relationship
     */
    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_entry_id');
    }

    /**
     * Get total debits
     */
    public function getTotalDebitsAttribute(): string
    {
        return $this->lines()
            ->where('debit', '>', 0)
            ->sum('debit');
    }

    /**
     * Get total credits
     */
    public function getTotalCreditsAttribute(): string
    {
        return $this->lines()
            ->where('credit', '>', 0)
            ->sum('credit');
    }

    /**
     * Check if entry is balanced
     */
    public function isBalanced(): bool
    {
        return bccomp($this->total_debits, $this->total_credits, config('accounting.decimal_scale', 6)) === 0;
    }

    /**
     * Check if entry is posted
     */
    public function isPosted(): bool
    {
        return $this->status === JournalEntryStatus::Posted;
    }

    /**
     * Check if entry is draft
     */
    public function isDraft(): bool
    {
        return $this->status === JournalEntryStatus::Draft;
    }

    /**
     * Check if entry is reversed
     */
    public function isReversed(): bool
    {
        return $this->status === JournalEntryStatus::Reversed;
    }

    /**
     * Get auditable attributes
     */
    public function getAuditableAttributes(): array
    {
        return [
            'entry_number',
            'entry_date',
            'description',
            'status',
        ];
    }
}
