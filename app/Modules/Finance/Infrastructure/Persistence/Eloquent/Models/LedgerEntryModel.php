<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class LedgerEntryModel extends FinanceModel
{
    protected $table = 'ledger_entries';

    protected $casts = [
        'metadata' => 'array',
        'posting_date' => 'date',
        'amount' => 'decimal:4',
        'running_balance' => 'decimal:4',
    ];
}
