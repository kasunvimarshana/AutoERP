<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AgreementAdjustmentType: string
{
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';

    public function label(): string
    {
        return match ($this) {
            self::CreditNote => 'Credit Note',
            self::DebitNote => 'Debit Note',
        };
    }
}
