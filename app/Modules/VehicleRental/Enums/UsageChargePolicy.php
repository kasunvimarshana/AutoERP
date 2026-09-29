<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum UsageChargePolicy: string
{
    case RecordedMinutesAndNights = 'recorded_minutes_and_nights_v1';
}
