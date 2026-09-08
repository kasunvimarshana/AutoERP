<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RunningChartAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Finalize = 'finalize';
    case Reverse = 'reverse';
}
