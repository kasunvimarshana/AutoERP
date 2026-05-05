<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Domain\ValueObjects;

enum FuelType: string
{
    case Petrol   = 'petrol';
    case Diesel   = 'diesel';
    case Electric = 'electric';
    case Hybrid   = 'hybrid';
    case Lpg      = 'lpg';
    case Other    = 'other';
}
