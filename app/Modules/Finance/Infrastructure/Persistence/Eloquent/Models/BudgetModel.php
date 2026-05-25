<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class BudgetModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'budgets';

    protected $casts = [
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];
}
