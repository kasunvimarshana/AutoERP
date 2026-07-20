<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAcMode: string
{
    case NonAc = 'non_ac';
    case FrontAc = 'front_ac';
    case DualAc = 'dual_ac';

    public function rateCode(): RentalRateCode
    {
        return match ($this) {
            self::NonAc => RentalRateCode::NonAc,
            self::FrontAc => RentalRateCode::FrontAc,
            self::DualAc => RentalRateCode::DualAc,
        };
    }
}
