<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class BankTransactionModel extends FinanceModel
{
    protected $table = 'bank_transactions';

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'balance' => 'decimal:4',
    ];
}
