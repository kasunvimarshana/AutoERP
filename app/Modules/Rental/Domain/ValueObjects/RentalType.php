<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\ValueObjects;

enum RentalType: string
{
    case SelfDrive  = 'self_drive';
    case WithDriver = 'with_driver';
}
