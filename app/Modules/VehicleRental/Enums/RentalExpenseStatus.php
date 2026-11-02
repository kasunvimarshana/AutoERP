<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Allocated = 'allocated';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Allocated => 'Allocated',
            self::Rejected => 'Rejected',
            self::Reversed => 'Reversed',
        };
    }
}
