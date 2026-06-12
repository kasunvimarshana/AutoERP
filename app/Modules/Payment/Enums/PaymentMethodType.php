<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case CreditNote = 'credit_note';
    case Advance = 'advance';
    case Deposit = 'deposit';
    case Wallet = 'wallet';
    case Custom = 'custom';
    case Bank = 'bank';
    case Online = 'online';
    case Transfer = 'transfer';
    case MobileWallet = 'mobile_wallet';
    case DebitNote = 'debit_note';
    case Other = 'other';
}
