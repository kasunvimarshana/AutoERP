<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class JournalEntryModel extends FinanceModel
{
    protected $table = 'journal_entries';

    protected $casts = [
        'metadata' => 'array',
        'entry_date' => 'date',
        'posting_date' => 'date',
        'is_reversed' => 'boolean',
        'posted_at' => 'datetime',
    ];
}
