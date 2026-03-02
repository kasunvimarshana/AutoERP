<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * JournalEntry entity.
 *
 * Represents an immutable double-entry bookkeeping journal entry.
 * Once posted, a journal entry cannot be edited — it must be reversed.
 */
class JournalEntry extends Model
{
    use HasTenant;

    /** Journal entry status constants */
    public const STATUS_DRAFT  = 'draft';
    public const STATUS_POSTED = 'posted';

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'fiscal_period_id',
        'reference_number',
        'description',
        'entry_date',
        'status',
        'posted_at',
        'reversed_at',
        'created_by',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'posted_at'    => 'datetime',
        'reversed_at'  => 'datetime',
    ];

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}
