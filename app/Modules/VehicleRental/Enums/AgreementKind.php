<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AgreementKind: string
{
    case Customer = 'customer';
    case Owner = 'owner';
}
