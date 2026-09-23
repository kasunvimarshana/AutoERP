<?php

declare(strict_types=1);

namespace Modules\Expense\Enums;

enum ExpenseStatus: string
{
    case Posting = 'posting';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
