<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum JobCardPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
