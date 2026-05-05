<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\ValueObjects;

enum JobType: string
{
    case Maintenance = 'maintenance';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Cleaning = 'cleaning';
    case Tyre = 'tyre';
    case Other = 'other';
}
