<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleFinanceInstallmentStatus: string
{
    case Scheduled = 'scheduled';
    case Due = 'due';
    case Overdue = 'overdue';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Due => 'Due',
            self::Overdue => 'Overdue',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Waived => 'Waived',
            self::Reversed => 'Reversed',
        };
    }
}
