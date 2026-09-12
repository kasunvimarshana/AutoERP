<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BaseChargeSource: string
{
    case Customer = 'vehicle_rental_customer_base_charge';
    case Owner = 'vehicle_rental_owner_base_charge';

    public static function forKind(AgreementKind $kind): self
    {
        return $kind === AgreementKind::Customer ? self::Customer : self::Owner;
    }
}
