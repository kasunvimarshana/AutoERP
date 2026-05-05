<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\ValueObjects;

enum ChargeType: string
{
    case Fuel     = 'fuel';
    case Damage   = 'damage';
    case Overtime = 'overtime';
    case Toll     = 'toll';
    case Other    = 'other';
}
