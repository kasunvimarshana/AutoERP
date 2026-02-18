<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case CHECK = 'check';
    case ONLINE = 'online';
    case MOBILE = 'mobile';
    case OTHER = 'other';

    /**
     * Get a human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::CHECK => 'Check',
            self::ONLINE => 'Online Payment',
            self::MOBILE => 'Mobile Payment',
            self::OTHER => 'Other',
        };
    }

    /**
     * Check if the payment method requires reference.
     */
    public function requiresReference(): bool
    {
        return in_array($this, [
            self::BANK_TRANSFER,
            self::CREDIT_CARD,
            self::DEBIT_CARD,
            self::CHECK,
            self::ONLINE,
        ]);
    }
}
