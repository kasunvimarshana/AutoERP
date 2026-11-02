<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleFinanceAgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';
}
