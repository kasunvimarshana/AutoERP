<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationSide: string
{
    case Customer = 'customer';
    case Owner = 'owner';

    public static function fromAgreementKind(RentalAgreementKind $kind): self
    {
        return match ($kind) {
            RentalAgreementKind::Customer => self::Customer,
            RentalAgreementKind::Owner => self::Owner,
        };
    }
}
