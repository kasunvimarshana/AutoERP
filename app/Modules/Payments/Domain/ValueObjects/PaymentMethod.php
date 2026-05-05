<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\ValueObjects;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Wallet = 'wallet';
    case Other = 'other';
}
