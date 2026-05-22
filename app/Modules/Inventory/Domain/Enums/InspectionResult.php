<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum InspectionResult: string
{
    case Pending = 'PENDING';
    case Passed = 'PASSED';
    case Failed = 'FAILED';
}
