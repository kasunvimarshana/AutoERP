<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Card = 'card';
    case Cheque = 'cheque';
    case Online = 'online';
    case Transfer = 'transfer';
    case MobileWallet = 'mobile_wallet';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Other = 'other';
}
