<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum StatementType: string
{
    case BalanceSheet = 'balance_sheet';
    case IncomeStatement = 'income_statement';
}
