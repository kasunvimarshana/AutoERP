<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Enums;

enum RecurringVoucherFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
