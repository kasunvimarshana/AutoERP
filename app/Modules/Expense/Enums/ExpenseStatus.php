<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Expense\Enums;

enum ExpenseStatus: string
{
    case Posting = 'posting';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
