<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
