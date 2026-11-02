<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalUsageEventApplicability: string
{
    case Customer = 'customer';
    case Owner = 'owner';
    case Both = 'both';
    case Internal = 'internal';

    public function appliesTo(RentalFinancialSide $side): bool
    {
        return match ($side) {
            RentalFinancialSide::Revenue => in_array($this, [self::Customer, self::Both], true),
            RentalFinancialSide::Cost => in_array($this, [self::Owner, self::Both], true),
        };
    }
}
