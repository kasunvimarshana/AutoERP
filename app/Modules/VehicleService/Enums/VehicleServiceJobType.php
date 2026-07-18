<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceJobType: string
{
    case FullService = 'full_service';
    case BodyWash = 'body_wash';
}
