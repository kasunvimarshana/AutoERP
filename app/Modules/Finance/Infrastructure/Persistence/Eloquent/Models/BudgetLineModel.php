<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class BudgetLineModel extends FinanceModel
{
    protected $table = 'budget_lines';

    protected $casts = [
        'metadata' => 'array',
        'period_1_amount' => 'decimal:4',
        'period_2_amount' => 'decimal:4',
        'period_3_amount' => 'decimal:4',
        'period_4_amount' => 'decimal:4',
        'period_5_amount' => 'decimal:4',
        'period_6_amount' => 'decimal:4',
        'period_7_amount' => 'decimal:4',
        'period_8_amount' => 'decimal:4',
        'period_9_amount' => 'decimal:4',
        'period_10_amount' => 'decimal:4',
        'period_11_amount' => 'decimal:4',
        'period_12_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];
}
