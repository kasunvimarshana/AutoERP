<?php

namespace App\Modules\Finance\Enums;

enum DebitCredit: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'Debit',
            self::CREDIT => 'Credit',
        };
    }
}
