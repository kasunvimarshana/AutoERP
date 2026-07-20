<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
}
