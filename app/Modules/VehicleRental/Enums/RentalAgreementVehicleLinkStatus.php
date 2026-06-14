<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementVehicleLinkStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';
}
