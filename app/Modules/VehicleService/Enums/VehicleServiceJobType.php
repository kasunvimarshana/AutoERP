<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceJobType: string
{
    case FullService = 'full_service';
    case BodyWash = 'body_wash';
    case OilChange = 'oil_change';
    case WheelAlignment = 'wheel_alignment';
    case Accessories = 'accessories';

    public function tracksMileage(): bool
    {
        return in_array($this, [self::FullService, self::OilChange, self::WheelAlignment], true);
    }
}
