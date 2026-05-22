<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Enums;

enum VoucherType: string
{
    case Expense = 'expense';
    case Income = 'income';
}
