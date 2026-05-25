<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class JournalEntryLineModel extends FinanceModel
{
    protected $table = 'journal_entry_lines';

    protected $casts = [
        'metadata' => 'array',
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:4',
        'base_debit_amount' => 'decimal:4',
        'base_credit_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
    ];
}
