<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * JournalEntryLine entity.
 *
 * Represents a single debit or credit line within a journal entry.
 * Amount is cast as string to ensure BCMath-safe arithmetic (no float).
 */
class JournalEntryLine extends Model
{
    use HasTenant;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_id',
        'type',
        'amount',
        'description',
    ];

    /**
     * Cast amount as string to preserve BCMath precision — never cast to float.
     */
    protected $casts = [
        'amount' => 'string',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
