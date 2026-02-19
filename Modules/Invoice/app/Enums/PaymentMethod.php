<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

/**
 * Payment Method Enum
 *
 * Defines the possible payment methods
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case BANK_TRANSFER = 'bank_transfer';
    case CHECK = 'check';
    case MOBILE_PAYMENT = 'mobile_payment';
    case OTHER = 'other';

    /**
     * Get all payment method values
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CHECK => 'Check',
            self::MOBILE_PAYMENT => 'Mobile Payment',
            self::OTHER => 'Other',
        };
    }
}
