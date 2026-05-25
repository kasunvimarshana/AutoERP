<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class FiscalPeriodModel extends FinanceModel
{
    protected $table = 'fiscal_periods';

    protected $casts = [
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
