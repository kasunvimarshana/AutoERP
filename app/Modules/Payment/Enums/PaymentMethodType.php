<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case MobileWallet = 'mobile_wallet';
    case DigitalWallet = 'digital_wallet';
    case DirectDebit = 'direct_debit';
    case Other = 'other';
}
