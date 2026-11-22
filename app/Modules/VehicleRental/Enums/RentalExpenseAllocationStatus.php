<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
