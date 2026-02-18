<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\JournalEntryFactory;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;

/**
 * Journal Entry Entity
 *
 * Represents a journal entry in double-entry bookkeeping.
 * Each entry must have balanced debits and credits.
 */
class JournalEntry extends Model
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
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'fiscal_period_id',
        'status',
        'total_debit',
        'total_credit',
        'currency',
        'posted_at',
        'posted_by',
        'reversed_entry_id',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    /**
     * Get journal entry lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get the fiscal period.
     */
    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    /**
     * Get the user who posted this entry.
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'posted_by');
    }

    /**
     * Get the reversed entry if this entry was reversed.
     */
    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_entry_id');
    }

    /**
     * Check if entry is posted.
     */
    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Check if entry is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if entry is reversed.
     */
    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * Check if entry is balanced.
     */
    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    /**
     * Calculate and update totals from lines.
     */
    public function calculateTotals(): void
    {
        $this->total_debit = $this->lines()->sum('debit_amount');
        $this->total_credit = $this->lines()->sum('credit_amount');
        $this->save();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): JournalEntryFactory
    {
        return JournalEntryFactory::new();
    }
}
