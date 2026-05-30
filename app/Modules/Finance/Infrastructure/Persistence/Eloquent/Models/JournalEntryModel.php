<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class JournalEntryModel extends FinanceModel
{
    protected $table = 'journal_entries';

    protected $casts = [
        'metadata' => 'array',
        'source_context' => 'array',
        'entry_date' => 'date',
        'posting_date' => 'date',
        'is_reversed' => 'boolean',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];
}
