<?php

namespace App\Modules\POS\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case CHEQUE = 'cheque';
    case MOBILE_MONEY = 'mobile_money';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CHEQUE => 'Cheque',
            self::MOBILE_MONEY => 'Mobile Money',
            self::CREDIT => 'Credit',
        };
    }

    public function requiresReference(): bool
    {
        return in_array($this, [self::CARD, self::BANK_TRANSFER, self::CHEQUE, self::MOBILE_MONEY]);
    }
}
