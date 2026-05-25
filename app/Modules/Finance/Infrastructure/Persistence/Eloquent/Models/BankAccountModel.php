<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class BankAccountModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'bank_accounts';

    protected $casts = [
        'metadata' => 'array',
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'last_reconciled_balance' => 'decimal:4',
        'last_reconciled_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
