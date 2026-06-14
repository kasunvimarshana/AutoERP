<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';
}
