<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseAllocationStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Invoiced = 'invoiced';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Confirmed',
            self::Invoiced => 'Invoiced',
            self::Reversed => 'Reversed',
        };
    }
}
