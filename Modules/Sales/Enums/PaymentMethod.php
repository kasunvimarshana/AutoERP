<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

/**
 * Payment Method Enum
 *
 * Represents payment methods for invoices.
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case CHECK = 'check';
    case ONLINE_PAYMENT = 'online_payment';
    case OTHER = 'other';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::CHECK => 'Check',
            self::ONLINE_PAYMENT => 'Online Payment',
            self::OTHER => 'Other',
        };
    }
}
