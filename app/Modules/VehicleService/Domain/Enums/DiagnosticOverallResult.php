<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum DiagnosticOverallResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Warning = 'warning';
    case NotApplicable = 'not_applicable';
}
