<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\ValueObjects;

class TransactionType
{
    public const PAYMENT         = 'payment';
    public const RECEIPT         = 'receipt';
    public const TRANSFER        = 'transfer';
    public const ADJUSTMENT      = 'adjustment';
    public const REFUND          = 'refund';
    public const JOURNAL         = 'journal';
    public const OPENING_BALANCE = 'opening_balance';

    public static function values(): array
    {
        return ['payment', 'receipt', 'transfer', 'adjustment', 'refund', 'journal', 'opening_balance'];
    }
}
