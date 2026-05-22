<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case VehicleService = 'vehicle_service';
    case VehicleRentalLessor = 'vehicle_rental_lessor';
    case VehicleRentalLessee = 'vehicle_rental_lessee';
}
