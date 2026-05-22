<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
