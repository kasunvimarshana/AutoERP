<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseType: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Maintenance = 'maintenance';
    case Fine = 'fine';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fuel => 'Fuel',
            self::Toll => 'Toll Fee',
            self::Maintenance => 'Maintenance / Repair',
            self::Fine => 'Traffic Fine',
            self::Other => 'Other Expense',
        };
    }
}
