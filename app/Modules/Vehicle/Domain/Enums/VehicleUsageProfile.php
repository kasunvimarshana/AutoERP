<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Enums;

enum VehicleUsageProfile: string
{
    case RentOnly = 'rent_only';
    case ServiceOnly = 'service_only';
    case Dual = 'dual';
    case Internal = 'internal';
}
