<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum ServiceOverallResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Warning = 'warning';
    case NotApplicable = 'not_applicable';
}
