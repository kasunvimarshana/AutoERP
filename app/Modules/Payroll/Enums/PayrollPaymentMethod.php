<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Enums;

enum PayrollPaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Cheque = 'cheque';
}
