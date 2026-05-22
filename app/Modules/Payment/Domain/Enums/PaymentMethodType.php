<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Cheque = 'cheque';
    case Other = 'other';
}
