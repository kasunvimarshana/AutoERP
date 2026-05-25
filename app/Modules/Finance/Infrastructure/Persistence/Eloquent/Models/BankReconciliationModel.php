<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class BankReconciliationModel extends FinanceModel
{
    protected $table = 'bank_reconciliations';

    protected $casts = [
        'metadata' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4',
        'statement_balance' => 'decimal:4',
        'difference' => 'decimal:4',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
