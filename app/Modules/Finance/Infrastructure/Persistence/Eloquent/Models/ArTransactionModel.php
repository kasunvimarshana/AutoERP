<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class ArTransactionModel extends FinanceModel
{
    protected $table = 'ar_transactions';

    protected $casts = [
        'metadata' => 'array',
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'exchange_rate' => 'decimal:4',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'is_reconciled' => 'boolean',
    ];
}
