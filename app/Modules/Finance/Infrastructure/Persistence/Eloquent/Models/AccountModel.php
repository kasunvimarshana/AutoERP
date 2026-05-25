<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class AccountModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'accounts';

    protected $casts = [
        'metadata' => 'array',
        'is_control_account' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_cash_account' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'allows_manual_posting' => 'boolean',
    ];
}
