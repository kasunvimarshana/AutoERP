<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalChargeInvoiceStatus: string
{
    case NotInvoiced = 'not_invoiced';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
}
