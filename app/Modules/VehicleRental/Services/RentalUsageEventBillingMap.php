<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalUsageEventType;

final class RentalUsageEventBillingMap
{
    public static function componentForEvent(RentalUsageEventType $eventType): RentalRateComponentCode
    {
        return match ($eventType) {
            RentalUsageEventType::Parking => RentalRateComponentCode::Parking,
            RentalUsageEventType::Toll => RentalRateComponentCode::Toll,
            RentalUsageEventType::Waiting => RentalRateComponentCode::Waiting,
            RentalUsageEventType::Outstation => RentalRateComponentCode::Outstation,
            RentalUsageEventType::Pass => RentalRateComponentCode::Pass,
            RentalUsageEventType::Fuel => RentalRateComponentCode::Fuel,
            RentalUsageEventType::Damage => RentalRateComponentCode::Damage,
            RentalUsageEventType::Repair => RentalRateComponentCode::Repair,
            RentalUsageEventType::Other => RentalRateComponentCode::OtherRecovery,
        };
    }

    public static function eventForComponent(RentalRateComponentCode $componentCode): ?RentalUsageEventType
    {
        return match ($componentCode) {
            RentalRateComponentCode::Parking => RentalUsageEventType::Parking,
            RentalRateComponentCode::Toll => RentalUsageEventType::Toll,
            RentalRateComponentCode::Waiting => RentalUsageEventType::Waiting,
            RentalRateComponentCode::Outstation => RentalUsageEventType::Outstation,
            RentalRateComponentCode::Pass => RentalUsageEventType::Pass,
            RentalRateComponentCode::Fuel => RentalUsageEventType::Fuel,
            RentalRateComponentCode::Damage => RentalUsageEventType::Damage,
            RentalRateComponentCode::Repair => RentalUsageEventType::Repair,
            RentalRateComponentCode::OtherRecovery => RentalUsageEventType::Other,
            default => null,
        };
    }

    /** @return array<string, string> */
    public static function eventComponentCodes(): array
    {
        $codes = [];
        foreach (RentalUsageEventType::cases() as $eventType) {
            $codes[$eventType->value] = self::componentForEvent($eventType)->value;
        }

        return $codes;
    }
}
