<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\ValueObjects;

enum CompensationType: string
{
    case Salary     = 'salary';
    case PerTrip    = 'per_trip';
    case Commission = 'commission';
}
