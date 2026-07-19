<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';
}
