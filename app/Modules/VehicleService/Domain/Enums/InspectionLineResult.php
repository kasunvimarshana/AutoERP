<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum InspectionLineResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Flag = 'flag';
    case NotTested = 'not_tested';
}
