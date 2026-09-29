<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum UsageChargeSource: string
{
    case Customer = 'vehicle_rental_customer_usage_charge';
    case Owner = 'vehicle_rental_owner_usage_charge';

    public static function forKind(AgreementKind $kind): self
    {
        return $kind === AgreementKind::Customer ? self::Customer : self::Owner;
    }
}
